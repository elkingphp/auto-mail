<?php

namespace App\Jobs;

use App\Models\Execution;
use App\Services\Delivery\FtpDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FtpDeliveryService $ftpService): void
    {
        Log::info("CleanupExpiredReportsJob: Starting cleanup");

        $deletedCount = 0;

        // Get executions that need cleanup (local file or FTP file still active)
        $executions = Execution::where('status', 'completed')
            ->where(function ($query) {
                $query->whereNotNull('output_path')
                      ->orWhere(function ($q) {
                          $q->whereNotNull('ftp_path')
                            ->whereNull('ftp_deleted_at');
                      });
            })
            ->with(['report', 'ftpServer'])
            ->cursor(); // Use cursor for memory efficiency with large datasets

        foreach ($executions as $execution) {
            if (!$execution->report) {
                continue;
            }

            $retentionDays = $execution->report->retention_days ?? 30;
            
            // Calculate expiry date from LAST DOWNLOAD if exists, otherwise created_at
            $expiryDate = $execution->last_downloaded_at 
                ? $execution->last_downloaded_at->addDays($retentionDays)
                : $execution->created_at->addDays($retentionDays);

            if (now()->greaterThan($expiryDate)) {
                $filesDeleted = false;

                // 1. Delete Local File
                if ($execution->output_path && Storage::exists($execution->output_path)) {
                    Storage::delete($execution->output_path);
                    Log::info("Deleted expired local report file", [
                        'execution_id' => $execution->id,
                        'file' => $execution->output_path
                    ]);
                    $execution->output_path = null;
                    $filesDeleted = true;
                }

                // 2. Delete FTP File
                if ($execution->ftp_path && !$execution->ftp_deleted_at) {
                    if ($execution->ftpServer) {
                        try {
                            $success = $ftpService->delete($execution->ftpServer, $execution->ftp_path);
                            
                            if ($success) {
                                $execution->ftp_deleted_at = now();
                                $execution->ftp_delete_status = 'success';
                                Log::info("Deleted expired FTP report file", [
                                    'execution_id' => $execution->id,
                                    'ftp_path' => $execution->ftp_path
                                ]);
                                $filesDeleted = true;
                            } else {
                                $execution->ftp_delete_status = 'failed';
                                Log::warning("Failed to delete expired FTP file", [
                                    'execution_id' => $execution->id,
                                    'ftp_path' => $execution->ftp_path
                                ]);
                            }
                        } catch (\Exception $e) {
                            $execution->ftp_delete_status = 'failed';
                            Log::error("Exception deleting expired FTP file", [
                                'execution_id' => $execution->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        // FTP server no longer linked, mark as unknown/failed but don't crash
                        $execution->ftp_delete_status = 'orphaned_server';
                        Log::warning("Cannot delete FTP file - server not found", [
                            'execution_id' => $execution->id
                        ]);
                    }
                }

                if ($filesDeleted) {
                    $deletedCount++;
                }

                $execution->save();
            }
        }

        Log::info("CleanupExpiredReportsJob: Completed", ['deleted_count' => $deletedCount]);
    }
}
