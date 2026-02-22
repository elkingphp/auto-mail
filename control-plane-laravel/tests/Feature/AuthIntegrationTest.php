<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AdminSeeder::class);
    }

    /**
     * Test successful login and audit logging.
     */
    public function test_login_successful_and_audited(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@system.local',
            'password' => 'admin123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['token', 'user' => ['id', 'name', 'email']],
                     'message'
                 ]);

        // Verify Audit Log (since AuditService dispatches a job, we can check if it was queued or if we use sync queue)
        // In .env.testing, QUEUE_CONNECTION=sync
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'resource_type' => 'user'
        ]);
    }

    /**
     * Test failed login and audit logging.
     */
    public function test_login_failed_and_audited(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@system.local',
            'password' => 'wrong'
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_failed',
            'resource_type' => 'user'
        ]);
    }
}
