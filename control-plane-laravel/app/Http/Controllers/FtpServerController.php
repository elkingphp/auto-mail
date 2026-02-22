<?php

namespace App\Http\Controllers;

use App\Models\FtpServer;
use App\Services\Delivery\FtpDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FtpServerController extends BaseController
{
    private FtpDeliveryService $service;

    public function __construct(FtpDeliveryService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return $this->sendResponse(FtpServer::all(), 'FTP servers retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'root_path' => 'nullable|string',
            'passive_mode' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $server = FtpServer::create($validated);
        return $this->sendResponse($server, 'FTP server created successfully.', 201);
    }

    public function show(FtpServer $ftpServer): JsonResponse
    {
        return $this->sendResponse($ftpServer, 'FTP server retrieved successfully.');
    }

    public function stats(FtpServer $ftpServer): JsonResponse
    {
        try {
            // Query executions directly associated with this FTP server
            $executions = \App\Models\Execution::where('ftp_server_id', $ftpServer->id)->get();

            $stats = [
                'total_files' => $executions->where('status', 'completed')->count(),
                'total_size' => $executions->sum('file_size') ?? 0,
                'failure_count' => $executions->where('status', 'failed')->count(),
                'unique_reports_count' => $executions->pluck('report_id')->unique()->count(),
                'last_executions' => \App\Http\Resources\ExecutionResource::collection(
                    $executions->sortByDesc('created_at')->take(10)
                )
            ];


            return $this->sendResponse($stats, 'FTP statistics retrieved successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("FTP Stats Error [{$ftpServer->id}]: " . $e->getMessage());
            return $this->sendError('Failed to load FTP statistics: ' . $e->getMessage(), [], 500);
        }
    }

    public function listFiles(Request $request, FtpServer $ftpServer): JsonResponse
    {
        $path = $request->query('path', '/');
        try {
            // Use enhanced listFiles from service which handles metadata
            $files = $this->service->listFiles($ftpServer, $path);
            return $this->sendResponse($files, 'Files retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to list files: ' . $e->getMessage());
        }
    }

    public function makeDirectory(Request $request, FtpServer $ftpServer): JsonResponse
    {
        $request->validate(['path' => 'required|string']);
        $success = $this->service->makeDirectory($ftpServer, $request->path);
        return $success ? $this->sendResponse([], 'Directory created.') : $this->sendError('Failed to create directory.');
    }

    public function deleteFile(Request $request, FtpServer $ftpServer): JsonResponse
    {
        $request->validate(['path' => 'required|string', 'type' => 'required|in:file,dir']);
        if ($request->type === 'dir') {
            $success = $this->service->deleteDirectory($ftpServer, $request->path);
        } else {
            $success = $this->service->delete($ftpServer, $request->path);
        }
        return $success ? $this->sendResponse([], 'Item deleted.') : $this->sendError('Failed to delete item.');
    }

    public function uploadFile(Request $request, FtpServer $ftpServer): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'file' => 'required|file'
        ]);
        
        $file = $request->file('file');
        $success = $this->service->uploadFile($ftpServer, $request->path . '/' . $file->getClientOriginalName(), fopen($file->getRealPath(), 'r'));
        
        return $success ? $this->sendResponse([], 'File uploaded.') : $this->sendError('Failed to upload file.');
    }
    
    public function downloadFile(Request $request, FtpServer $ftpServer): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate(['path' => 'required|string']);
        try {
            $content = $this->service->getFile($ftpServer, $request->path);
            if (!$content) return $this->sendError('File not found.', [], 404);
            
            return response($content)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . basename($request->path) . '"');
        } catch (\Exception $e) {
            return $this->sendError('Download failed: ' . $e->getMessage());
        }
    }

    public function update(Request $request, FtpServer $ftpServer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'host' => 'sometimes|string',
            'port' => 'sometimes|integer',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'root_path' => 'nullable|string',
            'passive_mode' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $ftpServer->update($validated);
        return $this->sendResponse($ftpServer, 'FTP server updated successfully.');
    }

    public function destroy(FtpServer $ftpServer): JsonResponse
    {
        $ftpServer->delete();
        return $this->sendResponse([], 'FTP server deleted successfully.', 204);
    }

    public function testConnection(Request $request): JsonResponse
    {
        if ($request->has('id')) {
            $server = FtpServer::find($request->id);
            if (!$server) return $this->sendError('Server not found.', [], 404);
        } else {
            $validated = $request->validate([
                'host' => 'required|string',
                'port' => 'required|integer',
            ]);
            $server = new FtpServer($request->all());
        }

        $success = $this->service->verifyConnection($server);
        
        if ($success) {
            return $this->sendResponse([], 'Connection successful.');
        } else {
            return $this->sendError('Connection failed.', [], 400);
        }
    }
}
