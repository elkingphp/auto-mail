<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ReportService
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function getAll(): Collection
    {
        $user = Auth::user();
        $query = Report::with(['service', 'dataSource', 'creator', 'department']);

        if ($user && !$user->isAdmin()) {
            $query->where('department_id', $user->department_id);
        }

        return $query->get();
    }

    public function create(array $data): Report
    {
        $user = Auth::user();
        $data['created_by'] = $user?->id;
        $data['department_id'] = $data['department_id'] ?? $user?->department_id;
        $data['delivery_mode'] = $this->calculateDeliveryMode($data);
        
        $report = Report::create($data);

        // Version 1
        $this->createVersion($report);

        if (isset($data['fields'])) {
            $report->fields()->createMany($data['fields']);
        }

        $this->auditService->logCreate('report', $report->id, $report->toArray());

        return $report;
    }

    public function update(Report $report, array $data): Report
    {
        $oldValues = $report->toArray();
        $data['delivery_mode'] = $this->calculateDeliveryMode($data);
        
        $report->update($data);

        // Create new version if definition changed
        if (isset($data['sql_definition']) || isset($data['visual_definition'])) {
            $this->createVersion($report);
        }

        if (isset($data['fields'])) {
            $report->fields()->delete(); // Simple replace strategy
            $report->fields()->createMany($data['fields']);
        }

        $this->auditService->logUpdate('report', $report->id, $oldValues, $report->fresh()->toArray());

        return $report;
    }

    public function createVersion(Report $report): void
    {
        $lastVersion = \App\Models\ReportVersion::where('report_id', $report->id)
            ->max('version_number') ?? 0;

        \App\Models\ReportVersion::create([
            'report_id' => $report->id,
            'version_number' => $lastVersion + 1,
            'type' => $report->type,
            'definition' => $report->type === 'visual' ? $report->visual_definition : $report->sql_definition,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }


    private function calculateDeliveryMode(array $data): string
    {
        $hasEmail = !empty($data['email_template_id'] ?? null);
        $hasFtp = !empty($data['ftp_server_id'] ?? null);
        
        if ($hasEmail && $hasFtp) return 'both';
        if ($hasEmail) return 'email';
        if ($hasFtp) return 'ftp';
        return 'none';
    }

    public function delete(Report $report): void
    {
        $report->delete();
    }

    public function getTelemetry(Report $report): array
    {
        // Get completed executions
        $executions = $report->executions()
            ->where('status', 'completed')
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->get();

        $totalExecutions = $executions->count();
        
        // Calculate average execution time in seconds using database-level aggregation
        $avgExecutionTime = $report->executions()
            ->where('status', 'completed')
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->avg(\Illuminate\Support\Facades\DB::raw('TIMESTAMPDIFF(SECOND, started_at, finished_at)'));
            
        $avgExecutionTime = round($avgExecutionTime ?? 0, 2);

        // Calculate FTP storage usage
        $ftpStorageBytes = 0;
        if ($report->ftpServer && in_array($report->delivery_mode, ['ftp', 'both'])) {
            try {
                $ftpService = app(\App\Services\Delivery\FtpDeliveryService::class);
                
                // Get all directories for this report
                $datePrefix = now()->format('Y-m-d');
                $reportSlug = \Illuminate\Support\Str::slug($report->name);
                
                // List all items in root
                $allFiles = $ftpService->listFiles($report->ftpServer, '/');
                $folderSuffix = "-{$report->name}";
                
                foreach ($allFiles as $item) {
                    // Check if directory matches our report pattern [YYYY-MM-DD]-[Report Name]
                    if ($item['type'] === 'dir' && str_ends_with($item['name'], $folderSuffix)) {
                        // Get files in this directory
                        $dirFiles = $ftpService->listFiles($report->ftpServer, $item['path']);
                        foreach ($dirFiles as $file) {
                            if ($file['type'] === 'file') {
                                $ftpStorageBytes += $file['size'] ?? 0;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to calculate FTP storage", [
                    'report_id' => $report->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_executions' => $totalExecutions,
            'avg_execution_time' => $avgExecutionTime,
            'ftp_storage_bytes' => $ftpStorageBytes,
            'ftp_storage_mb' => round($ftpStorageBytes / 1024 / 1024, 2)
        ];
    }
}
