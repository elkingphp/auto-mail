<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends BaseController
{
    public function __construct()
    {
        // Admin only Middleware or Policy should be here
        // For now, simple auth
    }

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        if ($request->has('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }

        if ($request->has('resource_id')) {
            $query->where('resource_id', $request->resource_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($request->get('limit', 50));

        return $this->sendResponse($logs, 'Audit logs retrieved successfully.');
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        return $this->sendResponse($auditLog->load('user'), 'Audit log retrieved successfully.');
    }
}
