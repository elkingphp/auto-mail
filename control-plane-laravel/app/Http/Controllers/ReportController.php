<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ReportController extends BaseController
{
    private ReportService $service;
    private \App\Services\AuditService $auditService;

    public function __construct(ReportService $service, \App\Services\AuditService $auditService)
    {
        $this->service = $service;
        $this->auditService = $auditService;
        $this->authorizeResource(Report::class, 'report');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports",
     *     tags={"Reports"},
     *     summary="List all reports",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ReportResource")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $reports = $this->service->getAll();
        return $this->sendResponse(ReportResource::collection($reports), 'Reports retrieved successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reports",
     *     tags={"Reports"},
     *     summary="Create a new report",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreReportRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Report created",
     *         @OA\JsonContent(ref="#/components/schemas/ReportResource")
     *     )
     * )
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->service->create($request->validated());
        return $this->sendResponse(new ReportResource($report), 'Report created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/{report}",
     *     tags={"Reports"},
     *     summary="Get a report by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="report",
     *         in="path",
     *         description="Report UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/ReportResource")
     *     )
     * )
     */
    public function show(Report $report): JsonResponse
    {
        $report->load(['service', 'dataSource', 'emailServer', 'emailTemplate', 'ftpServer', 'fields', 'filters', 'schedules', 'deliveryTargets']);
        return $this->sendResponse(new ReportResource($report), 'Report retrieved successfully.');
    }


    /**
     * @OA\Put(
     *     path="/api/v1/reports/{report}",
     *     tags={"Reports"},
     *     summary="Update a report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="report",
     *         in="path",
     *         description="Report UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateReportRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report updated",
     *         @OA\JsonContent(ref="#/components/schemas/ReportResource")
     *     )
     * )
     */
    public function update(UpdateReportRequest $request, Report $report): JsonResponse
    {
        $updatedReport = $this->service->update($report, $request->validated());
        return $this->sendResponse(new ReportResource($updatedReport), 'Report updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/reports/{report}",
     *     tags={"Reports"},
     *     summary="Delete a report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="report",
     *         in="path",
     *         description="Report UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Report deleted"
     *     )
     * )
     */
    public function destroy(Report $report): JsonResponse
    {
        $oldValues = $report->toArray();
        $this->service->delete($report);
        $this->auditService->logDelete('report', $report->id, $oldValues);
        return $this->sendResponse([], 'Report deleted successfully.', 204);
    }
    /**
     * @OA\Post(
     *     path="/api/v1/reports/preview",
     *     tags={"Reports"},
     *     summary="Preview SQL query results",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sql", "data_source_id"},
     *             @OA\Property(property="sql", type="string"),
     *             @OA\Property(property="data_source_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Query results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function preview(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'sql' => 'required|string',
            'data_source_id' => 'required|exists:data_sources,id'
        ]);

        $dataSource = \App\Models\DataSource::find($request->data_source_id);
        
        try {
            // SECURITY FIX: Validate SQL before execution
            $validator = new \App\Services\SqlValidatorService();
            $validator->validateSql($request->sql);
            
            // Setup dynamic connection
            $config = $dataSource->connection_config;
            $connectionName = 'preview_' . uniqid();
            
            $dbConfig = [
                'driver' => $dataSource->type === 'oracle' ? 'oracle' : ($dataSource->type === 'postgres' ? 'pgsql' : $dataSource->type),
                'host' => $config['host'] ?? 'localhost',
                'port' => $config['port'] ?? '3306',
                'database' => $config['database'] ?? '',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'charset' => $dataSource->type === 'oracle' ? 'AL32UTF8' : 'utf8mb4',
                'collation' => $dataSource->type === 'oracle' ? 'AL32UTF8' : 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ];

            if ($dataSource->type === 'oracle') {
                 // Logic lifted from DataSourceController::configureRuntimeConnection
                 // Prioritize service_name, then sid, then database
                 $service = $config['service_name'] ?? $config['sid'] ?? $config['database'] ?? '';
                 $dbConfig['service_name'] = $service;
                 $dbConfig['database'] = ''; // Clear database to avoid conflict in TNS construction
                 $dbConfig['charset'] = 'AL32UTF8';
            }

            // Fallback for simple testing if drivers are missing, but assuming environment is set
            \Illuminate\Support\Facades\Config::set("database.connections.{$connectionName}", $dbConfig);
            \Illuminate\Support\Facades\DB::purge($connectionName);
            
            // SECURITY FIX: Use validator to sanitize and limit query
            $driver = $dataSource->type === 'oracle' ? 'oracle' : ($dataSource->type === 'postgres' ? 'pgsql' : $dataSource->type);
            $sql = $validator->sanitizeAndLimit($request->sql, $driver, 50);

            $this->auditService->log('preview_sql', 'datasource', $dataSource->id, ['sql' => $sql]);

            $results = \Illuminate\Support\Facades\DB::connection($connectionName)->select($sql);
            
            return $this->sendResponse($results, 'Preview generated successfully.');

        } catch (\Exception $e) {
            return $this->sendError('SQL Validation Failed: ' . $e->getMessage(), [], 400);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/v1/reports/compile-visual",
     *     tags={"Reports"},
     *     summary="Compile visual AST to SQL",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"visual_definition", "data_source_id"},
     *             @OA\Property(property="visual_definition", type="object"),
     *             @OA\Property(property="data_source_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compiled SQL",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="string", example="SELECT * FROM users"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function compileVisual(\Illuminate\Http\Request $request, \App\Services\VisualQueryCompiler $compiler): JsonResponse
    {
        $request->validate([
            'visual_definition' => 'required|array',
            'data_source_id' => 'required|exists:data_sources,id'
        ]);

        try {
            $dataSource = \App\Models\DataSource::find($request->data_source_id);
            // Basic driver detection mapping
            $driver = $dataSource->type === 'oracle' ? 'oracle' : ($dataSource->type === 'postgres' ? 'pgsql' : 'mysql');
            
            $sql = $compiler->compile($request->visual_definition, $driver);
            
            return $this->sendResponse($sql, 'SQL compiled successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Compilation Failed: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/{report}/telemetry",
     *     tags={"Reports"},
     *     summary="Get report telemetry data",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="report",
     *         in="path",
     *         description="Report UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Telemetry data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_executions", type="integer"),
     *                 @OA\Property(property="avg_execution_time", type="number"),
     *                 @OA\Property(property="ftp_storage_bytes", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function telemetry(Report $report): JsonResponse
    {
        $telemetry = $this->service->getTelemetry($report);
        return $this->sendResponse($telemetry, 'Telemetry retrieved successfully.');
    }

    /**
     * Get all versions of a report.
     */
    public function versions(Report $report): JsonResponse
    {
        $versions = \App\Models\ReportVersion::where('report_id', $report->id)
            ->with('creator')
            ->orderBy('version_number', 'desc')
            ->get();
            
        return $this->sendResponse($versions, 'Report versions retrieved successfully.');
    }

    /**
     * Show a specific version.
     */
    public function showVersion(Report $report, $versionNumber): JsonResponse
    {
        $version = \App\Models\ReportVersion::where('report_id', $report->id)
            ->where('version_number', $versionNumber)
            ->with('creator')
            ->firstOrFail();

        return $this->sendResponse($version, 'Report version retrieved successfully.');
    }

    /**
     * Revert report to a specific version.
     */
    public function revertToVersion(Request $request, Report $report, $versionNumber): JsonResponse
    {
        $version = \App\Models\ReportVersion::where('report_id', $report->id)
            ->where('version_number', $versionNumber)
            ->firstOrFail();

        $oldValues = $report->toArray();

        // Update report definition based on version type
        if ($version->type === 'visual') {
            $report->update([
                'type' => 'visual',
                'visual_definition' => $version->definition,
                'sql_definition' => null // Or re-compile? Usually just null and let it re-compile on execute
            ]);
        } else {
            $report->update([
                'type' => 'sql',
                'sql_definition' => $version->definition,
                'visual_definition' => null
            ]);
        }

        // Create a new version for the "revert" action to keep history linear
        $this->service->createVersion($report);

        $this->auditService->log('revert_version', 'report', $report->id, $oldValues, [
            'reverted_to_version' => $versionNumber,
            'new_definition' => $version->definition
        ]);

        return $this->sendResponse(new ReportResource($report), 'Report reverted to version ' . $versionNumber . ' successfully.');
    }
}
