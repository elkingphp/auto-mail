<?php

namespace App\Services;

use App\Models\Execution;
use App\Models\Report;
use App\Models\FtpServer;
use App\Services\Delivery\EmailDeliveryService;
use App\Services\Delivery\FtpDeliveryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportExecutionService
{
    public function __construct(
        private EmailDeliveryService $emailService,
        private FtpDeliveryService $ftpService
    ) {}

    /**
     * Execute the complete report generation and delivery pipeline
     */
    public function execute(Execution $execution): void
    {
        Log::info("ReportExecutionService: Starting execution", ['id' => $execution->id]);

        // Update status to processing
        $execution->update([
            'status' => 'processing',
            'started_at' => now()
        ]);

        try {
            // Step 1: Generate the report file
            $filePath = $this->generateReportFile($execution);
            
            if (!$filePath || !Storage::exists($filePath)) {
                throw new \Exception("Report file generation failed - file does not exist");
            }

            // Update execution with file info
            $execution->update([
                'output_path' => $filePath,
                'file_size' => Storage::size($filePath)
            ]);

            Log::info("Report file generated", [
                'execution_id' => $execution->id,
                'path' => $filePath,
                'size' => $execution->file_size
            ]);

            // Step 2: Handle delivery based on report configuration
            $this->handleDelivery($execution);

            // Step 3: Mark as completed
            $execution->update([
                'status' => 'completed',
                'finished_at' => now()
            ]);

            Log::info("ReportExecutionService: Execution completed successfully", ['id' => $execution->id]);

        } catch (\Exception $e) {
            Log::error("ReportExecutionService: Execution failed", [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $execution->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'finished_at' => now()
            ]);

            throw $e;
        }
    }

    /**
     * Generate the actual report file by executing the query
     */
    private function generateReportFile(Execution $execution): string
    {
        $report = $execution->report;
        
        if (!$report) {
            throw new \Exception("Report not found for execution");
        }

        // Get the data source connection
        $dataSource = $report->dataSource;
        if (!$dataSource) {
            throw new \Exception("Data source not found for report");
        }

        // Execute the report query
        $data = $this->executeQuery($report, $dataSource, $execution->parameters ?? []);

        // Generate file based on format
        $format = $report->output_format ?? 'xlsx';
        $fileName = $this->generateFileName($report, $format);
        $filePath = "reports/{$fileName}";

        // Generate the file
        switch ($format) {
            case 'csv':
                $this->generateCsvFile($data, $filePath);
                break;
            case 'xlsx':
                $this->generateExcelFile($data, $filePath);
                break;
            case 'pdf':
                $this->generatePdfFile($data, $filePath, $report);
                break;
            default:
                throw new \Exception("Unsupported output format: {$format}");
        }

        return $filePath;
    }

    /**
     * Execute the report query against the data source
     */
    private function executeQuery(Report $report, $dataSource, array $parameters): array
    {
        // Build connection config
        $config = [
            'driver' => $dataSource->driver,
            'host' => $dataSource->host,
            'port' => $dataSource->port,
            'database' => $dataSource->database,
            'username' => $dataSource->username,
            'password' => decrypt($dataSource->password),
        ];

        // Create temporary connection
        config(['database.connections.temp_report' => $config]);

        try {
            $query = $report->sql_definition;
            
            // Replace parameters in query if any
            foreach ($parameters as $key => $value) {
                $query = str_replace(":{$key}", DB::connection('temp_report')->getPdo()->quote($value), $query);
            }

            $results = DB::connection('temp_report')->select($query);
            
            return json_decode(json_encode($results), true);
            
        } finally {
            // Clean up temp connection
            DB::purge('temp_report');
        }
    }

    /**
     * Generate filename with timestamp
     */
    private function generateFileName(Report $report, string $format): string
    {
        $name = Str::slug($report->name);
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "{$name}_{$timestamp}.{$format}";
    }

    /**
     * Generate CSV file
     */
    private function generateCsvFile(array $data, string $path): void
    {
        if (empty($data)) {
            throw new \Exception("No data to generate CSV");
        }

        $csv = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($csv, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($csv, $row);
        }
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        Storage::put($path, $content);
    }

    /**
     * Generate Excel file
     */
    private function generateExcelFile(array $data, string $path): void
    {
        // For now, generate CSV (can be upgraded to use PhpSpreadsheet later)
        $this->generateCsvFile($data, $path);
    }

    /**
     * Generate PDF file
     */
    private function generatePdfFile(array $data, string $path, Report $report): void
    {
        // For now, generate CSV (can be upgraded to use DomPDF/TCPDF later)
        $this->generateCsvFile($data, str_replace('.pdf', '.csv', $path));
    }

    /**
     * Handle delivery (FTP and/or Email)
     */
    private function handleDelivery(Execution $execution): void
    {
        $report = $execution->report;
        
        // Check if delivery is configured
        if (!$report->delivery_mode || $report->delivery_mode === 'none') {
            Log::info("No delivery configured for report", ['report_id' => $report->id]);
            return;
        }

        $filePath = storage_path('app/' . $execution->output_path);
        
        if (!file_exists($filePath)) {
            throw new \Exception("Report file not found for delivery: {$filePath}");
        }

        // Generate OTP if email delivery is enabled and template requires it
        $otp = null;
        if (in_array($report->delivery_mode, ['email', 'both']) && $report->emailTemplate?->require_otp) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $execution->update([
                'otp_hash' => bcrypt($otp),
                'otp_expires_at' => now()->addHours(24)
            ]);
        }

        // FTP Delivery
        if (in_array($report->delivery_mode, ['ftp', 'both'])) {
            $this->deliverViaFtp($execution, $filePath);
        }

        // Email Delivery
        if (in_array($report->delivery_mode, ['email', 'both'])) {
            $this->deliverViaEmail($execution, $filePath, $otp);
        }
    }

    /**
     * Deliver report via FTP
     */
    private function deliverViaFtp(Execution $execution, string $filePath): void
    {
        $report = $execution->report;
        $ftpServer = $report->ftpServer;

        if (!$ftpServer) {
            throw new \Exception("FTP server not configured for report");
        }

        // Create directory structure: (YYYY-MM-DD)-(report-name)/
        $datePrefix = now()->format('Y-m-d');
        $reportSlug = Str::slug($report->name);
        $reportDir = "({$datePrefix})-{$reportSlug}";
        
        // Ensure directory exists
        $this->ftpService->makeDirectory($ftpServer, $reportDir);
        
        $remotePath = $reportDir . "/" . basename($filePath);

        Log::info("Uploading to FTP", [
            'server' => $ftpServer->name,
            'remote_path' => $remotePath,
            'directory' => $reportDir
        ]);

        $success = $this->ftpService->upload($ftpServer, $filePath, $remotePath);

        if (!$success) {
            throw new \Exception("FTP upload failed to server: {$ftpServer->name}");
        }

        $execution->update([
            'ftp_server_id' => $ftpServer->id,
            'ftp_path' => $remotePath,
            'uploaded_at' => now()
        ]);

        Log::info("FTP upload successful", [
            'execution_id' => $execution->id,
            'ftp_path' => $remotePath
        ]);
    }

    /**
     * Deliver report via Email (Link and/or Attachment)
     */
    public function deliverViaEmail(Execution $execution, ?string $filePath = null, ?string $otp = null): void
    {
        $report = $execution->report;
        $emailServer = $report->emailServer;
        $emailTemplate = $report->emailTemplate;

        if (!$emailServer || !$emailTemplate) {
            Log::warning("Email delivery skipped: server or template missing", ['report_id' => $report->id]);
            return;
        }

        // Aggregate recipients
        $recipientsList = [];
        if ($report->default_recipients) {
            $recipientsList = array_merge($recipientsList, array_map('trim', explode(',', $report->default_recipients)));
        }
        if ($execution->notification_emails) {
            $recipientsList = array_merge($recipientsList, $execution->notification_emails);
        }
        
        $recipients = implode(',', array_unique(array_filter($recipientsList)));

        if (empty($recipients)) {
            Log::warning("Email delivery skipped: no recipients", ['execution_id' => $execution->id]);
            return;
        }

        // Prepare template variables
        $downloadController = app(\App\Http\Controllers\DownloadController::class);
        $downloadLink = $downloadController->generateLink($execution);

        $variables = [
            'report_name' => $report->name,
            'execution_date' => $execution->finished_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            'download_link' => $downloadLink,
            'otp_code' => $otp ?? $execution->otp_code,
        ];

        Log::info("Sending notification email", [
            'execution_id' => $execution->id,
            'recipients' => $recipients,
            'has_otp' => !is_null($variables['otp_code'])
        ]);

        try {
            $attachments = $filePath ? [$filePath] : [];
            
            $success = $this->emailService->send(
                $emailServer,
                $recipients,
                $emailTemplate,
                $variables,
                $attachments
            );

            $execution->update([
                'email_sent_at' => now(),
                'email_status' => $success ? 'success' : 'failed',
                'email_failure_reason' => $success ? null : 'Email service returned false'
            ]);

            if (!$success) {
                Log::error("Email delivery failed for execution", ['id' => $execution->id]);
            }

        } catch (\Exception $e) {
            $execution->update([
                'email_sent_at' => now(),
                'email_status' => 'failed',
                'email_failure_reason' => $e->getMessage()
            ]);
            Log::error("Exception during email delivery", [
                'execution_id' => $execution->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
