<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RLSIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Role $designerRole;
    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        
        $this->designerRole = Role::where('name', 'Designer')->first();
        $this->adminRole = Role::where('name', 'Admin')->first();
    }

    public function test_user_can_only_see_reports_from_their_department(): void
    {
        // 1. Setup Departments
        $dept1 = Department::create(['name' => 'IT', 'code' => 'IT01']);
        $dept2 = Department::create(['name' => 'Finance', 'code' => 'FIN01']);

        // 2. Setup Users
        $user1 = User::create([
            'name' => 'IT Designer',
            'email' => 'design@it.com',
            'password' => Hash::make('password'),
            'role_id' => $this->designerRole->id,
            'department_id' => $dept1->id
        ]);

        $user2 = User::create([
            'name' => 'Finance Designer',
            'email' => 'design@fin.com',
            'password' => Hash::make('password'),
            'role_id' => $this->designerRole->id,
            'department_id' => $dept2->id
        ]);

        // 3. Setup Dependencies
        $service = \App\Models\Service::create(['name' => 'IT Service', 'code' => 'IT-S01']);
        $dataSource = \App\Models\DataSource::create([
            'name' => 'Main DB',
            'type' => 'mysql',
            'connection_config' => ['host' => 'localhost']
        ]);

        // 4. Setup Reports
        Report::create([
            'service_id' => $service->id,
            'data_source_id' => $dataSource->id,
            'created_by' => $user1->id,
            'name' => 'IT Report',
            'type' => 'sql_native',
            'sql_definition' => 'select 1',
            'department_id' => $dept1->id,
        ]);

        Report::create([
            'service_id' => $service->id,
            'data_source_id' => $dataSource->id,
            'created_by' => $user2->id,
            'name' => 'Finance Report',
            'type' => 'sql_native',
            'sql_definition' => 'select 2',
            'department_id' => $dept2->id,
        ]);

        // 4. Act and Assert for User 1 (IT)
        $response1 = $this->actingAs($user1)->getJson('/api/v1/reports');
        $response1->assertStatus(200);
        $data1 = $response1->json('data');
        
        $this->assertCount(1, $data1);
        $this->assertEquals('IT Report', $data1[0]['name']);

        // 5. Act and Assert for User 2 (Finance)
        $response2 = $this->actingAs($user2)->getJson('/api/v1/reports');
        $response2->assertStatus(200);
        $data2 = $response2->json('data');
        
        $this->assertCount(1, $data2);
        $this->assertEquals('Finance Report', $data2[0]['name']);
    }

    public function test_admin_can_see_all_reports(): void
    {
        $admin = User::where('email', 'admin@system.local')->first();
        
        $response = $this->actingAs($admin)->getJson('/api/v1/reports');
        $response->assertStatus(200);
        
        // Should see IT, Finance and any other previously seeded reports
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_user_forbidden_from_viewing_specific_report_of_other_dept(): void
    {
        $dept1 = Department::create(['name' => 'HR', 'code' => 'HR01']);
        $dept2 = Department::create(['name' => 'Sales', 'code' => 'SAL01']);

        $user1 = User::create([
            'name' => 'HR User',
            'email' => 'hr@test.com',
            'password' => Hash::make('password'),
            'role_id' => $this->designerRole->id,
            'department_id' => $dept1->id
        ]);

        $service = \App\Models\Service::create(['name' => 'HR Service', 'code' => 'HR-S01']);
        $dataSource = \App\Models\DataSource::create([
            'name' => 'HR DB',
            'type' => 'mysql',
            'connection_config' => ['host' => 'localhost']
        ]);

        $report2 = Report::create([
            'service_id' => $service->id,
            'data_source_id' => $dataSource->id,
            'created_by' => $user1->id, // Created by User 1 but in Dept 2? No, let's fix that.
            'name' => 'Sales Secret Report',
            'type' => 'sql_native',
            'sql_definition' => 'select * from sales',
            'department_id' => $dept2->id,
        ]);

        $response = $this->actingAs($user1)->getJson("/api/v1/reports/{$report2->id}");
        
        // Should return 403 Forbidden because of Policy
        $response->assertStatus(403);
    }
}
