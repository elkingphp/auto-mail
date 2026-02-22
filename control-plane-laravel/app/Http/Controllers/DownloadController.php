<?php

namespace App\Http\Controllers;

use App\Models\Execution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(
        private \App\Services\Delivery\EmailDeliveryService $emailService,
        private \App\Services\AuditService $auditService
    ) {}

    public function generateLink(Execution $execution): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:8080'));
        return rtrim($frontendUrl, '/') . "/dl/{$execution->id}";
    }

    /**
     * Show download page (with OTP form if required)
     */
    public function show(Request $request, string $execution)
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:8080'));
        return redirect()->to(rtrim($frontendUrl, '/') . "/dl/{$execution}");
    }

    /**
     * Validate OTP
     */
    public function validateOtp(Request $request, string $execution)
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        $exec = Execution::findOrFail($execution);

        // Check if OTP is required
        if (!$exec->otp_hash) {
            return $request->wantsJson() 
                ? response()->json(['success' => true])
                : redirect()->route('download.report', ['execution' => $exec->id]);
        }

        // Check if OTP is used
        if ($exec->otp_used_at) {
            $msg = 'This OTP has already been used. Please request a new link.';
            return $request->wantsJson()
                ? response()->json(['message' => $msg, 'needs_reissue' => true], 422)
                : back()->withErrors(['otp' => $msg]);
        }

        // Check if OTP is expired
        if ($exec->otp_expires_at && now()->greaterThan($exec->otp_expires_at)) {
            $msg = 'OTP has expired. Please request a new link.';
            return $request->wantsJson()
                ? response()->json(['message' => $msg, 'needs_reissue' => true], 422)
                : back()->withErrors(['otp' => $msg]);
        }

        // Validate OTP
        if (!Hash::check($request->otp, $exec->otp_hash)) {
            $msg = 'Invalid OTP code.';
            return $request->wantsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->withErrors(['otp' => $msg]);
        }

        // Mark OTP as validated
        $exec->update(['otp_validated' => true]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'OTP validated successfully.',
                'download_url' => route('download.file', ['execution' => $exec->id])
            ]);
        }

        return redirect()->route('download.report', ['execution' => $exec->id])
            ->with('success', 'OTP validated successfully. You can now download the report.');
    }

    /**
     * Request a new OTP and download link
     */
    public function requestNewLink(Request $request, string $execution)
    {
        $exec = Execution::findOrFail($execution);
        $report = $exec->report;

        // 1. Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // 2. Clear old state and set new OTP + Expiry
        $exec->update([
            'otp_hash' => bcrypt($otp),
            'otp_expires_at' => now()->addHours(24),
            'otp_validated' => false,
            'otp_used_at' => null,
            // Reset Remote Retention Timer
            'expires_at' => now()->addHours(24), // Or based on retention_period
        ]);

        // 3. Re-send Email
        $emailServer = $report->emailServer;
        $emailTemplate = $report->emailTemplate;
        
        if ($emailServer && $emailTemplate) {
            $recipients = $report->default_recipients;
            if ($exec->notification_emails) {
                $recipientsText = implode(',', $exec->notification_emails);
                $recipients = $recipients ? $recipients . ',' . $recipientsText : $recipientsText;
            }

            if ($recipients) {
                $this->emailService->send(
                    $emailServer,
                    $recipients,
                    $emailTemplate,
                    [
                        'report_name' => $report->name,
                        'execution_date' => $exec->created_at->toDateTimeString(),
                        'download_link' => route('download.report', ['execution' => $exec->id]),
                        'otp_code' => $otp,
                    ]
                );
            }
        }

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => 'A new OTP has been sent to your email.'])
            : back()->with('success', 'A new OTP has been sent to your email.');
    }

    /**
     * Download the file using StreamedResponse (proxy from FTP)
     */
    public function download(Request $request, string $execution)
    {
        // 1. Solve Large File Streaming Timeout
        set_time_limit(0); 
        ini_set('memory_limit', '512M');

        $exec = Execution::findOrFail($execution);

        // Check OTP validation if required
        if ($exec->otp_hash && (!$exec->otp_validated || $exec->otp_used_at)) {
            abort(403, 'OTP validation required or OTP already used.');
        }

        $ftpServer = $exec->report->ftpServer;
        if (!$ftpServer) {
            abort(404, 'Remote storage configuration not found.');
        }

        // Configure dynamic FTP disk
        config([
            'filesystems.disks.temp_ftp' => [
                'driver' => 'ftp',
                'host' => $ftpServer->host,
                'username' => $ftpServer->username,
                'password' => $ftpServer->password,
                'port' => (int)($ftpServer->port ?? 21),
                'root' => '/',
                'passive' => true,
                'ssl' => false,
                'timeout' => 30,
            ],
        ]);

        $remotePath = $exec->output_path;
        if (!$remotePath || !Storage::disk('temp_ftp')->exists($remotePath)) {
            Log::error("Report file not found on FTP", ['path' => $remotePath, 'execution_id' => $exec->id]);
            abort(404, 'Report file not found on remote storage.');
        }

        // Log Download
        $this->auditService->log('download_report', 'execution', $exec->id);

        // Invalidate OTP after first successful download attempt starts
        $exec->update([
            'otp_used_at' => now(),
            'otp_validated' => false,
            'download_count' => $exec->download_count + 1,
            'last_downloaded_at' => now()
        ]);

        $fileName = basename($remotePath);
        $fileSize = Storage::disk('temp_ftp')->size($remotePath);
        $mimeType = $this->getMimeType($remotePath);

        return new StreamedResponse(function () use ($remotePath) {
            $stream = Storage::disk('temp_ftp')->readStream($remotePath);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Content-Length' => $fileSize,
        ]);
    }

    /**
     * Get MIME type based on file extension
     */
    private function getMimeType(string $fileName): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        return match(strtolower($extension)) {
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };
    }
}
