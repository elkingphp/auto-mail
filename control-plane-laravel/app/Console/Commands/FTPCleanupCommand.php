<?php

namespace App\Console\Commands;

use App\Models\Execution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FTPCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:ftp-cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired report files from the remote FTP server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting FTP cleanup process...');

        $expiredExecutions = Execution::where('expires_at', '<', now())
            ->whereNull('deleted_at')
            ->whereNotNull('output_path')
            ->with('report.ftpServer')
            ->get();

        $this->info("Found {$expiredExecutions->count()} expired executions.");

        foreach ($expiredExecutions as $exec) {
            $ftpServer = $exec->report->ftpServer;
            if (!$ftpServer) {
                $this->error("No FTP server configured for execution {$exec->id}. Skipping.");
                continue;
            }

            try {
                // Configure dynamic FTP disk
                $diskName = "temp_cleanup_ftp_{$exec->id}";
                config([
                    "filesystems.disks.{$diskName}" => [
                        'driver' => 'ftp',
                        'host' => $ftpServer->host,
                        'username' => $ftpServer->username,
                        'password' => decrypt($ftpServer->password),
                        'port' => (int)($ftpServer->port ?? 21),
                        'root' => '/',
                        'passive' => true,
                        'ssl' => false,
                        'timeout' => 30,
                    ],
                ]);

                $disk = Storage::disk($diskName);
                $filePath = $exec->output_path;

                if ($disk->exists($filePath)) {
                    $disk->delete($filePath);
                    $this->info("Deleted file: {$filePath}");

                    // Try to delete parent directory if it's empty
                    $parentDir = dirname($filePath);
                    if ($parentDir !== '.' && $parentDir !== '/' && empty($disk->allFiles($parentDir))) {
                        $disk->deleteDirectory($parentDir);
                        $this->info("Deleted empty parent directory: {$parentDir}");
                    }

                    $exec->update([
                        'deleted_at' => now(),
                        'ftp_delete_status' => 'success'
                    ]);
                } else {
                    $this->warn("File not found on FTP: {$filePath}");
                    $exec->update([
                        'deleted_at' => now(), // Mark as processed anyway
                        'ftp_delete_status' => 'not_found'
                    ]);
                }

                Log::info("FTP Cleanup: Successfully processed execution {$exec->id}");
            } catch (\Exception $e) {
                Log::error("FTP Cleanup Failed for execution {$exec->id}: " . $e->getMessage());
                $this->error("Failed to cleanup execution {$exec->id}: " . $e->getMessage());
                
                $exec->update([
                    'ftp_delete_status' => 'failed',
                    'error_log' => $exec->error_log . "\n[Cleanup Error] " . $e->getMessage()
                ]);
            }
        }

        $this->info('FTP cleanup process completed.');
    }
}
