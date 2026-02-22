<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Execution;
use App\Services\Delivery\EmailDeliveryService;
use App\Services\Delivery\FtpDeliveryService;
use Illuminate\Support\Facades\Log;

class ScheduleExecutor
{
    private EmailDeliveryService $emailService;
    private FtpDeliveryService $ftpService;

    public function __construct(
        EmailDeliveryService $emailService,
        FtpDeliveryService $ftpService
    ) {
        $this->emailService = $emailService;
        $this->ftpService = $ftpService;
    }

    /**
     * Execute delivery for a schedule run.
     * This is intended to be called by the worker/job after file generation.
     *
     * @param string $scheduleId
     * @param string $executionId (Optional context logging)
     * @param string $generatedFilePath Absolute path to the report file
     * @param array $variables Optional context variables for templates (e.g. execution_time)
     * @return array Result summary
     */
    public function executeDelivery(string $scheduleId, string $generatedFilePath, string $executionId = null, array $variables = []): array
    {
        $schedule = Schedule::with(['emailServer', 'emailTemplate', 'ftpServers'])->find($scheduleId);

        if (!$schedule) {
            Log::error("ScheduleExecutor: Schedule not found", ['id' => $scheduleId]);
            return ['status' => 'failed', 'reason' => 'Schedule not found'];
        }

        $results = [
            'email' => null,
            'ftp' => [],
            'errors' => []
        ];

        // Prepare Variables
        $variables['date'] = now()->toDateString();
        $variables['datetime'] = now()->toDateTimeString();
        $variables['filename'] = basename($generatedFilePath);
        $variables['report_name'] = $schedule->report ? $schedule->report->name : 'Report';

        // OTP Generation
        $otp = null;
        if ($schedule->emailTemplate && $schedule->emailTemplate->require_otp) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $variables['otp_code'] = $otp;
            
            if ($executionId) {
                Execution::where('id', $executionId)->update(['otp_code' => $otp]);
            }
        }

        // Download Link
        if ($executionId) {
             $execution = Execution::find($executionId);
             if ($execution) {
                 $downloadController = app(\App\Http\Controllers\DownloadController::class);
                 $variables['download_link'] = $downloadController->generateLink($execution);
             }
        }
        
        // --- 1. Email Delivery ---
        if ($this->shouldSendEmail($schedule)) {
            if (!$schedule->emailServer) {
                $results['email'] = 'skipped_no_server';
                $results['errors'][] = 'Email mode enabled but no email server configured.';
            } else {
                $recipients = $schedule->recipients ?? ($schedule->report ? $schedule->report->default_recipients : null); 
                
                if (empty($recipients)) {
                    $results['email'] = 'skipped_no_recipients';
                    $results['errors'][] = 'Email mode enabled but no recipients defined.';
                } else {
                    $success = $this->emailService->send(
                        $schedule->emailServer,
                        $recipients,
                        $schedule->emailTemplate,
                        $variables,
                        [$generatedFilePath]
                    );

                    $results['email'] = $success ? 'success' : 'failed';
                    if (!$success) $results['errors'][] = 'Email delivery failed (check logs).';
                }
            }
        }

        // --- 2. FTP Delivery ---
        if ($this->shouldUploadFtp($schedule)) {
            $ftpServers = $schedule->ftpServers;
            
            if ($ftpServers->isEmpty()) {
                 $results['ftp_global'] = 'skipped_no_servers';
                 $results['errors'][] = 'FTP mode enabled but no FTP servers linked.';
            } else {
                foreach ($ftpServers as $server) {
                    // Generate remote filename
                    $remoteName = $variables['filename']; 
                    // Optional: Prefix with date or report folder?
                    // $remoteName = date('Y-m-d') . '_' . $remoteName;

                    $success = $this->ftpService->upload($server, $generatedFilePath, $remoteName);
                    
                    $results['ftp'][$server->name] = $success ? 'success' : 'failed';
                    if (!$success) $results['errors'][] = "FTP upload to {$server->name} failed.";
                }
            }
        }

        return $results;
    }

    private function shouldSendEmail(Schedule $schedule): bool
    {
        return in_array($schedule->delivery_mode, ['email', 'email_and_ftp', 'both']);
    }

    private function shouldUploadFtp(Schedule $schedule): bool
    {
        return in_array($schedule->delivery_mode, ['ftp', 'ftp_only', 'email_and_ftp', 'both']);
    }
}
