<?php

namespace Tests\Unit;

use App\Jobs\ProcessAuditLog;
use App\Services\AuditService;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    public function test_it_dispatches_audit_job(): void
    {
        Bus::fake();

        $service = new AuditService();
        $service->log('test_action', 'test_resource', 'res-123');

        Bus::assertDispatched(ProcessAuditLog::class, function ($job) {
            return $job->data['action'] === 'test_action' && 
                   $job->data['resource_type'] === 'test_resource' &&
                   $job->data['resource_id'] === 'res-123';
        });
    }

    public function test_shortcut_methods(): void
    {
        Bus::fake();

        $service = new AuditService();
        $service->logCreate('report', 'rep-123', ['name' => 'New']);

        Bus::assertDispatched(ProcessAuditLog::class, function ($job) {
            return $job->data['action'] === 'create' && $job->data['resource_id'] === 'rep-123';
        });
    }
}
