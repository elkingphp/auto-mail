<?php

namespace App\Jobs;

use App\Models\Execution;
use App\Models\Report;
use App\Services\ReportExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    public function __construct(
        public string $executionId
    ) {}

    public function handle(): void
    {
        $execution = Execution::with(['report.dataSource', 'triggeredByUser'])->find($this->executionId);
        
        if (!$execution || !$execution->report) {
            Log::error("ExecuteReportJob: Execution or Report not found", ['id' => $this->executionId]);
            return;
        }

        $report = $execution->report;
        $triggeredByUser = $execution->triggeredByUser;
        $rlsDepartmentId = $triggeredByUser?->department_id;
        
        // Prepare detailed payload based on ENGINE_API.md
        $payload = [
            'job_id' => (string) \Illuminate\Support\Str::uuid(),
            'execution_id' => $execution->id,
            'report_id' => $report->id,
            'task_type' => 'execute',
            'priority' => $execution->priority ?? ($report->is_critical ? 'high' : 'medium'),
            'timeout_seconds' => $report->timeout_seconds ?? 3600,
            'retry_policy' => [
                'max_attempts' => $execution->max_retries ?? 3,
                'backoff_strategy' => 'exponential',
                'max_backoff_hours' => 24
            ],
            'notification_emails' => $execution->notification_emails ?? []
        ];

        // Handle SQL Definition (Pre-compile if Visual)
        if ($report->type === 'visual' && $report->visual_definition) {
            try {
                $compiler = app(\App\Services\VisualQueryCompiler::class);
                $driver = $report->dataSource?->type ? 
                    ($report->dataSource->type === 'oracle' ? 'oracle' : ($report->dataSource->type === 'postgres' ? 'pgsql' : 'mysql')) 
                    : 'mysql';
                
                $payload['sql_definition'] = $compiler->compile($report->visual_definition, $driver, $rlsDepartmentId);
            } catch (\Exception $e) {
                Log::error("ExecuteReportJob: Visual compilation failed", ['id' => $execution->id, 'error' => $e->getMessage()]);
                $execution->update([
                    'status' => 'failed',
                    'error_log' => "Visual Query Compilation Error: " . $e->getMessage(),
                    'finished_at' => now()
                ]);
                return;
            }
        } else {
            // For Native SQL, we might need a separate service to inject RLS if possible,
            // but for now, RLS is primarily for Visual Builder as requested.
            $payload['sql_definition'] = $report->sql_definition;
        }

        // Handle Parameterized Bindings
        if (!empty($execution->parameters)) {
            $payload['bindings'] = array_values($execution->parameters);
        } else {
            $payload['bindings'] = [];
        }

        try {
            // Push to Redis with priority support
            $queueName = 'rbdb_execution_queue';
            // In the future, we can push to specific priority queues:
            // if ($payload['priority'] === 'high') $queueName .= '_high';
            
            \Illuminate\Support\Facades\Redis::rpush($queueName, json_encode($payload));
            
            Log::info("ExecuteReportJob: Pushed to Redis queue [$queueName]", [
                'execution_id' => $execution->id,
                'job_id' => $payload['job_id'],
                'priority' => $payload['priority']
            ]);

            // Update status initially
            $execution->increment('retry_count');
            $execution->update([
                'status' => 'processing',
                'started_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::error("ExecuteReportJob: Failed to push to Redis", [
                'execution_id' => $this->executionId,
                'error' => $e->getMessage()
            ]);
            
            $execution->update([
                'status' => 'failed',
                'error_log' => "Redis Queue Error: " . $e->getMessage(),
                'finished_at' => now()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $execution = Execution::find($this->executionId);
        
        if ($execution) {
            $execution->update([
                'status' => 'failed',
                'error_log' => "Job failed after {$this->tries} attempts: " . $exception->getMessage(),
                'finished_at' => now()
            ]);
        }
    }
}
