<?php

namespace Tests\Feature;

use App\Models\DataSource;
use App\Models\Execution;
use App\Models\Report;
use App\Models\User;
use App\Models\Service;
use App\Models\Department;
use App\Jobs\ExecuteReportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class EngineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure Redis is clean for the test key
        Redis::del('rbdb_execution_queue');
    }

    /**
     * Test that ExecuteReportJob correctly pushes a payload to Redis.
     */
    public function test_execute_report_job_pushes_to_redis(): void
    {
        // 1. Setup Data
        $department = Department::create(['name' => 'IT', 'code' => 'IT001']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $service = Service::create(['name' => 'Support']);

        $dataSource = DataSource::create([
            'name' => 'MySQL Test',
            'type' => 'mysql',
            'connection_config' => [
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'rbdb_test',
                'username' => 'rbdb',
                'password' => 'password'
            ]
        ]);

        $report = Report::create([
            'name' => 'Test Report',
            'service_id' => $service->id,
            'type' => 'sql',
            'sql_definition' => 'SELECT * FROM operational_data',
            'data_source_id' => $dataSource->id,
            'created_by' => $user->id,
            'department_id' => $user->department_id
        ]);

        $execution = Execution::create([
            'report_id' => $report->id,
            'triggered_by' => $user->id,
            'status' => 'pending'
        ]);

        // 2. Run the Job
        $job = new ExecuteReportJob($execution->id);
        $job->handle();

        // 3. Verify Redis
        $queueKey = 'rbdb_execution_queue';
        $this->assertEquals(1, Redis::llen($queueKey));

        $payloadJson = Redis::lpop($queueKey);
        $payload = json_decode($payloadJson, true);

        $this->assertEquals($execution->id, $payload['execution_id']);
        $this->assertEquals($report->id, $payload['report_id']);
        $this->assertEquals('execute', $payload['task_type']);
        $this->assertEquals($report->sql_definition, $payload['sql_definition']);
        $this->assertArrayHasKey('job_id', $payload);
        
        // 4. Verify Execution Status Update
        $execution->refresh();
        $this->assertEquals('processing', $execution->status);
        $this->assertNotNull($execution->started_at);
    }

    /**
     * Test Visual Query Compilation and Redis Push.
     */
    public function test_visual_report_compilation_and_redis_push(): void
    {
        // 1. Setup Data
        $department = Department::create(['name' => 'Sales', 'code' => 'SAL01']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $service = Service::create(['name' => 'Commercial']);

        $dataSource = DataSource::create([
            'name' => 'Postgres Test',
            'type' => 'postgres',
            'connection_config' => [
                'host' => 'localhost',
                'port' => 5432,
                'database' => 'rbdb_test',
                'username' => 'rbdb',
                'password' => 'password'
            ]
        ]);

        $visualDefinition = [
            'table' => 'operational_data',
            'columns' => ['user_id', 'amount']
        ];

        $report = Report::create([
            'name' => 'Visual Test',
            'service_id' => $service->id,
            'type' => 'visual',
            'visual_definition' => $visualDefinition,
            'data_source_id' => $dataSource->id,
            'created_by' => $user->id,
            'department_id' => $user->department_id
        ]);

        $execution = Execution::create([
            'report_id' => $report->id,
            'triggered_by' => $user->id,
            'status' => 'pending'
        ]);

        // 2. Run the Job
        $job = new ExecuteReportJob($execution->id);
        $job->handle();

        // 3. Verify Redis Payload (Check if compiled SQL is present)
        $payloadJson = Redis::lpop('rbdb_execution_queue');
        $payload = json_decode($payloadJson, true);

        $this->assertStringContainsString('SELECT', $payload['sql_definition']);
        $this->assertStringContainsString('operational_data', $payload['sql_definition']);
        $this->assertStringContainsString('user_id', $payload['sql_definition']);
    }
}
