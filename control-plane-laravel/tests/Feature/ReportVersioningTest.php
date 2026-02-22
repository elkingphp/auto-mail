<?php

namespace Tests\Feature;

use App\Models\DataSource;
use App\Models\Department;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportVersioningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Service $service;
    private DataSource $dataSource;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        
        $this->admin = User::where('email', 'admin@system.local')->first();
        $this->service = Service::create(['name' => 'Test Svc', 'code' => 'TEST01']);
        $this->dataSource = DataSource::create([
            'name' => 'Test DB',
            'type' => 'mysql',
            'connection_config' => ['host' => 'locahost']
        ]);
    }

    public function test_automatic_versioning_on_update(): void
    {
        // 1. Create Report via API
        $response = $this->actingAs($this->admin)->postJson("/api/v1/reports", [
            'service_id' => $this->service->id,
            'data_source_id' => $this->dataSource->id,
            'name' => 'Vers Test',
            'type' => 'sql',
            'sql_definition' => 'SELECT 1'
        ]);

        $response->assertStatus(201);
        $reportId = $response->json('data.id');

        // Check Version 1 exists
        $this->assertDatabaseHas('report_versions', [
            'report_id' => $reportId,
            'version_number' => 1,
            'definition' => 'SELECT 1'
        ]);

        // 2. Update via API
        $this->actingAs($this->admin)->putJson("/api/v1/reports/{$reportId}", [
            'sql_definition' => 'SELECT 2'
        ])->assertStatus(200);

        // 3. Verify Version 2 exists
        $this->assertDatabaseHas('report_versions', [
            'report_id' => $reportId,
            'version_number' => 2,
            'definition' => 'SELECT 2'
        ]);
    }

    public function test_list_and_revert_versions(): void
    {
        // 1. Setup Report with multiple versions
        $response = $this->actingAs($this->admin)->postJson("/api/v1/reports", [
            'service_id' => $this->service->id,
            'data_source_id' => $this->dataSource->id,
            'name' => 'Revert Test',
            'type' => 'sql',
            'sql_definition' => 'SELECT 1'
        ]);
        $response->assertStatus(201);
        
        $reportId = $response->json('data.id');

        $this->actingAs($this->admin)->putJson("/api/v1/reports/{$reportId}", ['sql_definition' => 'SELECT 2'])->assertStatus(200);
        $this->actingAs($this->admin)->putJson("/api/v1/reports/{$reportId}", ['sql_definition' => 'SELECT 3'])->assertStatus(200);

        // 2. List versions
        $response = $this->actingAs($this->admin)->getJson("/api/v1/reports/{$reportId}/versions");
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        // 3. Revert to Version 1 (V1)
        $revertResponse = $this->actingAs($this->admin)->postJson("/api/v1/reports/{$reportId}/versions/1/revert");
        $revertResponse->assertStatus(200);
        $this->assertEquals('SELECT 1', $revertResponse->json('data.sql_definition'));

        // 4. Verify linear history (Version 4 created with V1 definition)
        $this->assertDatabaseHas('report_versions', [
            'report_id' => $reportId,
            'version_number' => 4,
            'definition' => 'SELECT 1'
        ]);
    }
}
