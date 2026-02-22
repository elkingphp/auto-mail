<?php

namespace App\Console\Commands;

use App\Models\Execution;
use App\Models\Report;
use App\Services\Delivery\FtpDeliveryService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupExecutionsCommand extends Command
{
    protected $signature = 'app:cleanup-executions';
    protected $description = 'Cleanup old report executions based on retention settings';

    public function handle()
    {
        $reports = Report::where('retention_days', '>', 0)->get();

        foreach ($reports as $report) {
            $threshold = Carbon::now()->subDays($report->retention_days);
            
            $executions = Execution::where('report_id', $report->id)
                ->where('created_at', '<', $threshold)
                ->whereNotNull('output_path')
                ->get();

            if ($executions->isEmpty()) continue;

            $this->info("Cleaning up {$executions->count()} executions for report: {$report->name}");

            foreach ($executions as $execution) {
                $this->deleteFile($execution);
            }
        }
    }

    protected function deleteFile(Execution $execution)
    {
        $schedule = $execution->schedule;
        if (!$schedule) return;

        $ftpServers = $schedule->ftpServers;
        $service = app(FtpDeliveryService::class);

        foreach ($ftpServers as $server) {
            try {
                $service->rm($server, $execution->output_path, 'file');
            } catch (\Exception $e) {
                $this->error("Failed to delete file from FTP ({$server->name}): {$e->getMessage()}");
            }
        }

        // Mark as pruned
        $execution->update([
            'output_path' => null,
            'status' => 'pruned'
        ]);
    }
}
