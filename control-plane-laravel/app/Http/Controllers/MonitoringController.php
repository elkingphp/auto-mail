<?php

namespace App\Http\Controllers;

use App\Models\Execution;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MonitoringController extends BaseController
{
    public function index(): JsonResponse
    {
        $stats = [
            'queue' => [
                'pending_jobs' => $this->getQueueSize(),
                'active_executions' => Execution::where('status', 'processing')->count(),
                'failed_last_24h' => Execution::where('status', 'failed')
                    ->where('updated_at', '>=', now()->subDay())
                    ->count(),
            ],
            'performance' => [
                'avg_execution_time_last_24h' => round(Execution::where('status', 'completed')
                    ->where('finished_at', '>=', now()->subDay())
                    ->avg(DB::raw('TIMESTAMPDIFF(SECOND, started_at, finished_at)')), 2),
                'success_rate' => $this->calculateSuccessRate(),
            ],
            'system' => [
                'disk_usage' => $this->getDiskUsage(),
                'memory_usage' => $this->getMemoryUsage(),
            ]
        ];

        return $this->sendResponse($stats, 'Monitoring stats retrieved successfully.');
    }

    private function getQueueSize(): int
    {
        try {
            // Assuming Redis is used for queue
            return Redis::llen('queues:default') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculateSuccessRate(): float
    {
        $total = Execution::where('created_at', '>=', now()->subDay())->count();
        if ($total === 0) return 100.0;
        
        $success = Execution::where('status', 'completed')
            ->where('created_at', '>=', now()->subDay())
            ->count();
            
        return round(($success / $total) * 100, 2);
    }

    private function getDiskUsage(): array
    {
        $free = disk_free_space("/");
        $total = disk_total_space("/");
        return [
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_percent' => round((($total - $free) / $total) * 100, 2)
        ];
    }

    private function getMemoryUsage(): array
    {
        $mem = memory_get_usage(true);
        return [
            'used_mb' => round($mem / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit')
        ];
    }
}
