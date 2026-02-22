<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataSourceController;
use App\Http\Controllers\DeliveryTargetController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportFieldController;
use App\Http\Controllers\ReportFilterController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ExecutionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health Checks (Public)
    Route::get('health/live', [\App\Http\Controllers\HealthController::class, 'liveness']);
    Route::get('health/ready', [\App\Http\Controllers\HealthController::class, 'readiness']);

    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Public Download Routes (no auth required - uses signed URLs)
    Route::prefix('download')->group(function () {
        Route::get('report/{execution}', [\App\Http\Controllers\DownloadController::class, 'show'])->name('download.report');
        Route::post('report/{execution}/validate-otp', [\App\Http\Controllers\DownloadController::class, 'validateOtp'])->name('download.validate-otp');
        Route::post('report/{execution}/request-new-otp', [\App\Http\Controllers\DownloadController::class, 'requestNewLink'])
            ->middleware('throttle:otp-resend')
            ->name('download.request-new-otp');
        Route::get('report/{execution}/file', [\App\Http\Controllers\DownloadController::class, 'download'])->name('download.file');
    });

    // Protected Resources
    Route::middleware(['auth:sanctum'])->group(function () {
        // Broadcast Auth
        \Illuminate\Support\Facades\Broadcast::routes();

        // System Metrics
        Route::get('system/metrics', [\App\Http\Controllers\HealthController::class, 'metrics']);
        Route::get('system/monitoring', [\App\Http\Controllers\MonitoringController::class, 'index']);
        
        // Services
        Route::apiResource('services', ServiceController::class);

        // Data Sources
        Route::post('data-sources/test', [DataSourceController::class, 'testConnection']);
        Route::get('data-sources/{data_source}/schema', [DataSourceController::class, 'schema']);
        Route::get('data-sources/{data_source}/tables', [DataSourceController::class, 'tables']);
        Route::get('data-sources/{data_source}/columns', [DataSourceController::class, 'columns']);
        Route::post('data-sources/{data_source}/test', [DataSourceController::class, 'testConnection']);
        Route::apiResource('data-sources', DataSourceController::class);

        // Audit Logs
        Route::get('audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index']);
        Route::get('audit-logs/{audit_log}', [\App\Http\Controllers\AuditLogController::class, 'show']);

        // Reports
        Route::post('reports/compile-visual', [ReportController::class, 'compileVisual']);
        Route::post('reports/preview', [ReportController::class, 'preview'])->middleware('throttle:10,1');
        Route::get('reports/{report}/telemetry', [ReportController::class, 'telemetry']);
        Route::get('reports/{report}/versions', [ReportController::class, 'versions']);
        Route::get('reports/{report}/versions/{version}', [ReportController::class, 'showVersion']);
        Route::post('reports/{report}/versions/{version}/revert', [ReportController::class, 'revertToVersion']);
        Route::apiResource('reports', ReportController::class);

        // Report Fields
        Route::apiResource('report-fields', ReportFieldController::class);
        Route::apiResource('report-filters', ReportFilterController::class);

        // Delivery Infrastructure
        Route::post('email-servers/test', [\App\Http\Controllers\EmailServerController::class, 'testConnection']);
        Route::get('email-servers/{email_server}/stats', [\App\Http\Controllers\EmailServerController::class, 'stats']);
        Route::apiResource('email-servers', \App\Http\Controllers\EmailServerController::class);

        Route::post('ftp-servers/test', [\App\Http\Controllers\FtpServerController::class, 'testConnection']);
        Route::get('ftp-servers/{ftp_server}/stats', [\App\Http\Controllers\FtpServerController::class, 'stats']);
        Route::get('ftp-servers/{ftp_server}/ls', [\App\Http\Controllers\FtpServerController::class, 'listFiles']);
        Route::post('ftp-servers/{ftp_server}/mkdir', [\App\Http\Controllers\FtpServerController::class, 'makeDirectory']);
        Route::post('ftp-servers/{ftp_server}/rm', [\App\Http\Controllers\FtpServerController::class, 'deleteFile']);
        Route::post('ftp-servers/{ftp_server}/upload', [\App\Http\Controllers\FtpServerController::class, 'uploadFile']);
        Route::get('ftp-servers/{ftp_server}/download', [\App\Http\Controllers\FtpServerController::class, 'downloadFile']);
        Route::apiResource('ftp-servers', \App\Http\Controllers\FtpServerController::class);
        
        Route::get('email-templates/{email_template}/stats', [\App\Http\Controllers\EmailTemplateController::class, 'stats']);
        Route::post('email-templates/{email_template}/test-send', [\App\Http\Controllers\EmailTemplateController::class, 'sendTest']);
        Route::apiResource('email-templates', \App\Http\Controllers\EmailTemplateController::class);

        // Schedules
        Route::apiResource('schedules', ScheduleController::class);

        // Delivery Targets
        Route::apiResource('delivery-targets', DeliveryTargetController::class);

        // Executions
        Route::post('executions', [ExecutionController::class, 'store'])->middleware('throttle:5,1');
        Route::get('executions/{execution}/preview', [ExecutionController::class, 'previewContent']);
        Route::apiResource('executions', ExecutionController::class)->only(['index', 'update', 'show']);

        // User Management
        Route::get('users/notifications', [\App\Http\Controllers\UserController::class, 'notifications']);
        Route::post('users/notifications/{id}/read', [\App\Http\Controllers\UserController::class, 'markNotificationRead']);
        Route::post('users/notifications/read-all', [\App\Http\Controllers\UserController::class, 'markAllNotificationsRead']);
        Route::delete('users/notifications/clear-all', [\App\Http\Controllers\UserController::class, 'clearAllNotifications']);
        Route::apiResource('users', \App\Http\Controllers\UserController::class);
        Route::get('roles', [\App\Http\Controllers\RoleController::class, 'index']);
        Route::apiResource('departments', \App\Http\Controllers\DepartmentController::class);
    });
});
