<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailTemplateController extends BaseController
{
    public function index(): JsonResponse
    {
        return $this->sendResponse(EmailTemplate::all(), 'Templates retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'subject' => 'required|string',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'require_otp' => 'boolean',
        ]);

        $template = EmailTemplate::create($validated);
        return $this->sendResponse($template, 'Template created successfully.', 201);
    }

    public function show(EmailTemplate $emailTemplate): JsonResponse
    {
        return $this->sendResponse($emailTemplate, 'Template retrieved successfully.');
    }

    public function stats(EmailTemplate $emailTemplate): JsonResponse
    {
        $executions = \App\Models\Execution::whereHas('schedule', function($q) use ($emailTemplate) {
            $q->where('email_template_id', $emailTemplate->id);
        })->get();

        $stats = [
            'total_usage' => $executions->count(),
            'success_count' => $executions->where('status', 'completed')->count(),
            'failure_count' => $executions->where('status', 'failed')->count(),
            'reports' => \App\Http\Resources\ReportResource::collection(
                \App\Models\Report::whereHas('schedules', function($q) use ($emailTemplate) {
                    $q->where('email_template_id', $emailTemplate->id);
                })->get()
            )
        ];

        return $this->sendResponse($stats, 'Template statistics retrieved successfully.');
    }

    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'subject' => 'sometimes|string',
            'body_html' => 'sometimes|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'require_otp' => 'boolean',
        ]);

        $emailTemplate->update($validated);
        return $this->sendResponse($emailTemplate, 'Template updated successfully.');
    }

    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        $emailTemplate->delete();
        return $this->sendResponse([], 'Template deleted successfully.', 204);
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'server_id' => 'nullable|uuid|exists:email_servers,id'
        ]);

        $server = null;
        if ($validated['server_id'] ?? false) {
            $server = \App\Models\EmailServer::find($validated['server_id']);
        } else {
            $server = \App\Models\EmailServer::where('is_active', true)->first();
        }

        if (!$server) {
            return $this->sendError('No active email server found for testing.', 400);
        }

        $service = app(\App\Services\Delivery\EmailDeliveryService::class);
        $success = $service->send($server, $validated['email'], $emailTemplate, [
            'report_name' => 'STRICT-REALITY-TEST',
            'date' => now()->toDateTimeString(),
            'filename' => 'verification.xlsx',
            'download_link' => 'http://localhost:8000/dl/test-token',
            'otp_code' => '999999'
        ]);

        if ($success) {
            return $this->sendResponse([], 'Test email sent successfully via ' . $server->name);
        }

        return $this->sendError('Failed to send test email. Check logs.');
    }
}
