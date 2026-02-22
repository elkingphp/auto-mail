<?php

namespace App\Http\Controllers;

use App\Models\Execution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;


class ExecutionController extends BaseController
{
    private \App\Services\ReportExecutionService $reportService;
    private \App\Services\AuditService $auditService;

    public function __construct(
        \App\Services\ReportExecutionService $reportService,
        \App\Services\AuditService $auditService
    ) {
        $this->reportService = $reportService;
        $this->auditService = $auditService;
        $this->authorizeResource(Execution::class, 'execution');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/executions",
     *     tags={"Executions"},
     *     summary="List executions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Execution::query()->with(['report', 'triggeredByUser']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('report_id')) {
            $query->where('report_id', $request->report_id);
        }
        
        $query->orderBy('created_at', 'desc');

        return $this->sendResponse(\App\Http\Resources\ExecutionResource::collection($query->get()), 'Executions retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id',
            'parameters' => 'nullable|array',
            'notification_emails' => 'nullable|array',
            'notification_emails.*' => 'email'
        ]);

        $report = \App\Models\Report::findOrFail($request->report_id);

        $execution = Execution::create([
            'report_id' => $request->report_id,
            'status' => 'pending',
            'triggered_by' => $request->user()->id,
            'parameters' => $request->parameters,
            'notification_emails' => $request->notification_emails,
            'ftp_server_id' => $report->ftp_server_id // Link execution to FTP server for statistics
        ]);

        // Dispatch the job to execute the report asynchronously
        \App\Jobs\ExecuteReportJob::dispatch($execution->id);

        $this->auditService->log('trigger_execution', 'execution', $execution->id, null, $execution->toArray());

        return $this->sendResponse(new \App\Http\Resources\ExecutionResource($execution->load(['report', 'triggeredByUser'])), 'Execution triggered successfully.', 201);
    }

    public function update(Request $request, Execution $execution, \App\Services\ScheduleExecutor $executor): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('Execution Update Request', [
            'id' => $execution->id,
            'body' => $request->all()
        ]);
        $data = $request->all();

        // Handle OTP from engine
        if ($request->has('otp')) {
            $data['otp_code'] = $request->otp;
            $data['otp_hash'] = bcrypt($request->otp);
            $data['otp_expires_at'] = now()->addHours(24);
            $data['otp_validated'] = false;
        }

        \Illuminate\Support\Facades\DB::transaction(function() use ($execution, $data, $executor) {
            $execution->update($data);

            // Notify if completed
            if ($execution->status === 'completed') {
                if ($execution->schedule_id && $execution->output_path) {
                    // Trigger Delivery for Schedule
                    $results = $executor->executeDelivery(
                        $execution->schedule_id, 
                        $execution->output_path, 
                        $execution->id, 
                        [   
                            'execution_id' => $execution->id,
                            'execution_time' => $execution->finished_at?->toDateTimeString() ?? now()->toDateTimeString()
                        ]
                    );

                    if (!empty($results['errors'])) {
                        $errorString = implode("\n", $results['errors']);
                        $execution->error_log .= "\n[Delivery Errors]\n" . $errorString;
                        $execution->save();
                    }
                } else if ($execution->output_path) {
                    // Trigger Delivery for Manual Pulse
                    $this->reportService->deliverViaEmail($execution);
                }

                // Real-time Notification to the user who triggered it
                $userId = $execution->triggered_by;
                if ($userId) {
                    $triggeredBy = \App\Models\User::find($userId);
                    if ($triggeredBy) {
                        $details = [
                            'report_name' => $execution->report?->name ?? 'Untitled Report',
                            'execution_id' => $execution->id,
                            'download_url' => "/dl/" . $execution->id,
                            'type' => 'success',

                            'message' => 'Your report is ready for download.'
                        ];
                        Log::info("Dispatching ReportReadyNotification to User: " . $triggeredBy->id);
                        $triggeredBy->notify(new \App\Notifications\ReportReadyNotification($details));
                    }
                }
            } else if ($execution->status === 'failed') {
                // Auto-Retry logic
                if ($execution->retry_count < ($execution->max_retries ?? 3)) {
                    $delay = pow(2, $execution->retry_count) * 60; // 60s, 120s, 240s...
                    
                    \App\Jobs\ExecuteReportJob::dispatch($execution->id)->delay(now()->addSeconds($delay));
                    
                    $execution->update([
                        'status' => 'pending', // Re-queueing
                        'last_retry_at' => now(),
                        'error_log' => ($execution->error_log ?? '') . "\n[System] Scheduled retry " . ($execution->retry_count + 1) . " in $delay seconds."
                    ]);

                    Log::info("Execution failed, scheduled retry", ['id' => $execution->id, 'delay' => $delay]);
                    return; // Don't notify yet
                }

                // Notify failure after all retries
                $userId = $execution->triggered_by;
                $details = [
                    'report_name' => $execution->report?->name ?? 'Untitled Report',
                    'execution_id' => $execution->id,
                    'type' => 'error',
                    'message' => 'Report execution failed after ' . $execution->retry_count . ' attempts: ' . ($execution->error_log ?? 'Critical engine error')
                ];

                if ($userId) {
                    $triggeredBy = \App\Models\User::find($userId);
                    if ($triggeredBy) {
                        Log::warning("Dispatching Failure Notification to User: " . $triggeredBy->id);
                        $triggeredBy->notify(new \App\Notifications\ReportReadyNotification($details));
                    }
                }

                // Notify Admins for Critical Reports
                if ($execution->report?->is_critical) {
                    $admins = \App\Models\User::whereHas('role', function($q) {
                        $q->where('name', 'Admin');
                    })->get();

                    foreach ($admins as $admin) {
                        $admin->notify(new \App\Notifications\ReportReadyNotification(array_merge($details, [
                            'message' => '[CRITICAL] ' . $details['message']
                        ])));
                    }
                }
            }
        });

        // Broadcast the update for real-time UI
        broadcast(new \App\Events\ExecutionUpdated($execution->fresh(['report', 'triggeredByUser'])))->toOthers();

        return $this->sendResponse(new \App\Http\Resources\ExecutionResource($execution->load(['report', 'triggeredByUser'])), 'Execution updated successfully.');
    }

    public function show(Execution $execution): JsonResponse
    {
        return $this->sendResponse(new \App\Http\Resources\ExecutionResource($execution->load(['report', 'triggeredByUser'])), 'Execution retrieved successfully.');
    }

    /**
     * Preview the content of the generated report file.
     */
    public function previewContent(Request $request, Execution $execution)
    {
        if (!$execution->output_path) {
            return $this->sendError('Report has not generated an output file yet.', [], 404);
        }

        $this->auditService->log('preview_content', 'execution', $execution->id);

        // 1. Determine Storage Disk
        $disk = null;
        if ($execution->report->ftpServer) {
            // Configure dynamic FTP disk
            $ftpServer = $execution->report->ftpServer;
            config([
                'filesystems.disks.temp_ftp_preview' => [
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
            $disk = \Illuminate\Support\Facades\Storage::disk('temp_ftp_preview');
        } else {
            // Assume local or default disk
            $disk = \Illuminate\Support\Facades\Storage::disk('local'); // Adjust if you use s3 or other
        }

        // 2. check existence
        if (!$disk->exists($execution->output_path)) {
             return $this->sendError('File not found on storage.', [], 404);
        }

        // 3. Read first N lines
        $lines = [];
        $maxLines = 21; // Header + 20 rows
        $stream = $disk->readStream($execution->output_path);

        if ($stream) {
            while (($line = fgets($stream)) !== false) {
                $lines[] = trim($line);
                if (count($lines) >= $maxLines) break;
            }
            if (is_resource($stream)) fclose($stream);
        }

        if (empty($lines)) {
            return $this->sendResponse([], 'File is empty.');
        }

        // Simple CSV Parsing
        $delimiter = strpos($lines[0], ';') !== false ? ';' : ',';
        $headers = str_getcsv($lines[0], $delimiter);
        $rows = [];

        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], $delimiter);
            // Basic check if row length matches headers
            if (count($row) === count($headers)) {
                $item = array_combine($headers, $row);
            } else {
                // Fallback: use index as key or pad
                $item = [];
                foreach ($headers as $idx => $h) {
                    $item[$h] = $row[$idx] ?? '';
                }
            }
            $rows[] = $item;
        }

        return $this->sendResponse([
            'headers' => $headers,
            'rows' => $rows,
            'preview_limit' => 20
        ], 'Preview retrieved successfully.');
    }
}
