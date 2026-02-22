<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\Report;
use App\Models\Execution;
use App\Models\DataSource;

class HealthController extends BaseController
{
    /**
     * Basic Liveness Check
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness Check (Verify DB and Redis)
     */
    public function readiness(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $ready = ($checks['database']['status'] === 'ok' && $checks['redis']['status'] === 'ok');

        return response()->json([
            'status' => $ready ? 'ready' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $ready ? 200 : 503);
    }

    /**
     * Operational Metrics
     */
    public function metrics(): JsonResponse
    {
        return $this->sendResponse([
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
            ],
            'statistics' => [
                'total_reports' => Report::count(),
                'total_data_sources' => DataSource::count(),
                'pending_executions' => Execution::where('status', 'pending')->count(),
                'running_executions' => Execution::where('status', 'running')->count(),
                'failed_executions_24h' => Execution::where('status', 'failed')
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
            ],
            'last_execution' => Execution::latest()->first()?->only(['id', 'status', 'created_at']),
        ], 'System metrics retrieved.');
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
