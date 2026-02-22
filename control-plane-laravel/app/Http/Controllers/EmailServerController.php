<?php

namespace App\Http\Controllers;

use App\Models\EmailServer;
use App\Services\Delivery\EmailDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailServerController extends BaseController
{
    private EmailDeliveryService $service;

    public function __construct(EmailDeliveryService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return $this->sendResponse(EmailServer::all(), 'Email servers retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'encryption' => 'nullable|string|in:tls,ssl,none',
            'from_address' => 'required|email',
            'from_name' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $server = EmailServer::create($data);
        return $this->sendResponse($server, 'Email server created successfully.', 201);
    }

    public function show(EmailServer $emailServer): JsonResponse
    {
        return $this->sendResponse($emailServer, 'Email server retrieved successfully.');
    }

    public function stats(EmailServer $emailServer): JsonResponse
    {
        $executions = \App\Models\Execution::whereHas('schedule', function($q) use ($emailServer) {
            $q->where('email_server_id', $emailServer->id);
        })->get();

        $stats = [
            'total_sent' => $executions->where('status', 'completed')->count(),
            'success_count' => $executions->where('status', 'completed')->count(),
            'failure_count' => $executions->where('status', 'failed')->count(),
            'last_executions' => \App\Http\Resources\ExecutionResource::collection($executions->sortByDesc('created_at')->take(10))
        ];

        return $this->sendResponse($stats, 'Email gateway statistics retrieved successfully.');
    }

    public function update(Request $request, EmailServer $emailServer): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'host' => 'sometimes|string',
            'port' => 'sometimes|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'encryption' => 'nullable|string|in:tls,ssl,none',
            'from_address' => 'sometimes|email',
            'from_name' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $emailServer->update($data);
        return $this->sendResponse($emailServer, 'Email server updated successfully.');
    }

    public function destroy(EmailServer $emailServer): JsonResponse
    {
        $emailServer->delete();
        return $this->sendResponse([], 'Email server deleted successfully.', 204);
    }

    public function testConnection(Request $request): JsonResponse
    {
        // Allow testing unsaved or saved config
        if ($request->has('id')) {
            $server = EmailServer::find($request->id);
            if (!$server) return $this->sendError('Server not found.', [], 404);
        } else {
            // Test unsaved
            $request->validate([
                'host' => 'required|string',
                'port' => 'required|integer',
                'from_address' => 'required|email',
            ]);
            
            $server = new EmailServer($request->all());
            // Need to handle password specially since it's not encrypted if passed directly to model constructor 
            // but the model casts execute on set. 
            // However, verifyConnection expects a model.
            // If the model is not saved, the 'encrypted' cast might encrypt it in memory.
            // Let's verify. Yes, Eloquent casts work on attribute set.
        }

        $success = $this->service->verifyConnection($server);

        if ($success) {
            return $this->sendResponse([], 'Connection successful.');
        } else {
            return $this->sendError('Connection failed.', [], 400);
        }
    }
}
