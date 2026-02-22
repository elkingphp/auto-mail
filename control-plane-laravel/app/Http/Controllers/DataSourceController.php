<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDataSourceRequest;
use App\Http\Requests\UpdateDataSourceRequest;
use App\Http\Resources\DataSourceResource;
use App\Models\DataSource;
use App\Services\DataSourceService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class DataSourceController extends BaseController
{
    private DataSourceService $service;
    private \App\Services\AuditService $auditService;

    public function __construct(DataSourceService $service, \App\Services\AuditService $auditService)
    {
        $this->service = $service;
        $this->auditService = $auditService;
        $this->authorizeResource(DataSource::class, 'data_source');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/data-sources",
     *     tags={"Data Sources"},
     *     summary="List all data sources",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/DataSourceResource")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $sources = $this->service->getAll();
        return $this->sendResponse(DataSourceResource::collection($sources), 'Data Sources retrieved successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/data-sources",
     *     tags={"Data Sources"},
     *     summary="Create a new data source",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreDataSourceRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Data Source created",
     *         @OA\JsonContent(ref="#/components/schemas/DataSourceResource")
     *     )
     * )
     */
    public function store(StoreDataSourceRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        // Expert Validation: Test connection before saving
        $testResult = $this->performConnectionTest($data['type'], $data['connection_config']);
        if (!$testResult['success']) {
            return $this->sendError('Validation failed: Could not connect to database. ' . $testResult['message'], [], 422);
        }

        $source = $this->service->create($data);
        $this->auditService->logCreate('datasource', $source->id, $source->toArray());

        return $this->sendResponse(new DataSourceResource($source), 'Data Source created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/data-sources/{dataSource}",
     *     tags={"Data Sources"},
     *     summary="Get a data source by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="dataSource",
     *         in="path",
     *         description="DataSource UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DataSourceResource")
     *     )
     * )
     */
    public function show(DataSource $dataSource): JsonResponse
    {
        return $this->sendResponse(new DataSourceResource($dataSource), 'Data Source retrieved successfully.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/data-sources/{dataSource}",
     *     tags={"Data Sources"},
     *     summary="Update a data source",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="dataSource",
     *         in="path",
     *         description="DataSource UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateDataSourceRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data Source updated",
     *         @OA\JsonContent(ref="#/components/schemas/DataSourceResource")
     *     )
     * )
     */
    public function update(UpdateDataSourceRequest $request, DataSource $data_source): JsonResponse
    {
        $dataSource = $data_source;
        $data = $request->validated();
        $oldValues = $dataSource->toArray();

        // Expert Validation: Test connection before saving
        $testResult = $this->performConnectionTest($data['type'] ?? $dataSource->type, $data['connection_config'] ?? $dataSource->connection_config);
        if (!$testResult['success']) {
            return $this->sendError('Validation failed: Could not connect to database. ' . $testResult['message'], [], 422);
        }

        $updatedSource = $this->service->update($dataSource, $data);
        $this->auditService->logUpdate('datasource', $updatedSource->id, $oldValues, $updatedSource->fresh()->toArray());

        return $this->sendResponse(new DataSourceResource($updatedSource), 'Data Source updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/data-sources/{dataSource}",
     *     tags={"Data Sources"},
     *     summary="Delete a data source",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="dataSource",
     *         in="path",
     *         description="DataSource UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Data Source deleted"
     *     )
     * )
     */
    public function destroy(DataSource $dataSource): JsonResponse
    {
        $oldValues = $dataSource->toArray();
        $this->service->delete($dataSource);
        $this->auditService->logDelete('datasource', $dataSource->id, $oldValues);

        return $this->sendResponse([], 'Data Source deleted successfully.', 204);
    }
    /**
     * @OA\Get(
     *     path="/api/v1/data-sources/{dataSource}/schema",
     *     tags={"Data Sources"},
     *     summary="Get schema (tables) for a data source",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Schema retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function schema(DataSource $data_source): JsonResponse
    {
        try {
            $dataSource = $data_source; // Internal reference
            $config = $dataSource->connection_config;
            $connectionName = 'schema_' . uniqid();
            
            $driver = $this->configureRuntimeConnection($config, $dataSource->type, $connectionName);

            $schema = [];
            $connection = \Illuminate\Support\Facades\DB::connection($connectionName);

            if ($driver === 'mysql') {
                $dbName = $config['database'] ?? '';
                $results = $connection->select("
                    SELECT TABLE_NAME, COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = ? 
                    ORDER BY TABLE_NAME, ORDINAL_POSITION
                ", [$dbName]);

                foreach ($results as $row) {
                    $schema[$row->TABLE_NAME][] = $row->COLUMN_NAME;
                }
            } elseif ($driver === 'oracle') {
                $results = $connection->select("
                    SELECT table_name, column_name 
                    FROM user_tab_columns 
                    ORDER BY table_name, column_id
                ");

                foreach ($results as $row) {
                    $schema[$row->table_name][] = $row->column_name;
                }
            } elseif ($driver === 'pgsql') {
                 $results = $connection->select("
                    SELECT table_name, column_name 
                    FROM information_schema.columns 
                    WHERE table_schema = 'public'
                    ORDER BY table_name, ordinal_position
                ");

                 foreach ($results as $row) {
                     $schema[$row->table_name][] = $row->column_name;
                 }
            } elseif ($driver === 'sqlsrv') {
                 $results = $connection->select("
                    SELECT TABLE_NAME, COLUMN_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    ORDER BY TABLE_NAME, ORDINAL_POSITION
                ");
                
                foreach ($results as $row) {
                     $schema[$row->TABLE_NAME][] = $row->COLUMN_NAME;
                 }
            }

            return $this->sendResponse($schema, 'Schema retrieved successfully.');

        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch schema: ' . $e->getMessage());
        }
    }
     /**
     * @OA\Get(
     *     path="/api/v1/data-sources/{dataSource}/tables",
     *     tags={"Data Sources"},
     *     summary="Get tables only for a data source",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tables retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function tables(DataSource $data_source): JsonResponse
    {
        try {
            $dataSource = $data_source;
            $config = $dataSource->connection_config;
            $connectionName = 'tables_' . uniqid();
            
            $driver = $this->configureRuntimeConnection($config, $dataSource->type, $connectionName);

            $connection = \Illuminate\Support\Facades\DB::connection($connectionName);
            $tables = [];

            if ($driver === 'mysql') {
                $dbName = $config['database'] ?? '';
                $results = $connection->select("
                    SELECT TABLE_NAME 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_SCHEMA = ?
                    ORDER BY TABLE_NAME
                ", [$dbName]);
                foreach ($results as $row) { $tables[] = $row->TABLE_NAME; }
            } elseif ($driver === 'oracle') {
                $results = $connection->select("
                    SELECT table_name FROM user_tables 
                    UNION 
                    SELECT view_name as table_name FROM user_views
                ");
                foreach ($results as $row) { $tables[] = $row->table_name; }
            } elseif ($driver === 'pgsql') {
                 $results = $connection->select("
                    SELECT table_name 
                    FROM information_schema.tables 
                    WHERE table_schema = 'public'
                    ORDER BY table_name
                ");
                 foreach ($results as $row) { $tables[] = $row->table_name; }
            } elseif ($driver === 'sqlsrv') {
                 $results = $connection->select("
                    SELECT TABLE_NAME
                    FROM INFORMATION_SCHEMA.TABLES
                    ORDER BY TABLE_NAME
                ");
                foreach ($results as $row) { $tables[] = $row->TABLE_NAME; }
            }

            sort($tables);
            return $this->sendResponse($tables, 'Tables retrieved successfully.');

        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch tables: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/data-sources/{dataSource}/columns",
     *     tags={"Data Sources"},
     *     summary="Get columns for a specific table",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="table",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Columns retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function columns(DataSource $data_source): JsonResponse
    {
        try {
            $tableName = request('table');
            if (!$tableName) return $this->sendError('Table name is required');

            $dataSource = $data_source;
            $config = $dataSource->connection_config;
            $connectionName = 'cols_' . uniqid();
            
            $driver = $this->configureRuntimeConnection($config, $dataSource->type, $connectionName);

            $connection = \Illuminate\Support\Facades\DB::connection($connectionName);
            $columns = [];

            if ($driver === 'mysql') {
                $dbName = $config['database'] ?? '';
                $results = $connection->select("
                    SELECT COLUMN_NAME, COLUMN_COMMENT
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                    ORDER BY ORDINAL_POSITION
                ", [$dbName, $tableName]);
                foreach ($results as $row) { 
                    $columns[] = [
                        'name' => $row->COLUMN_NAME,
                        'label' => $row->COLUMN_COMMENT ?: $row->COLUMN_NAME
                    ]; 
                }
            } elseif ($driver === 'oracle') {
                $results = $connection->select("
                    SELECT c.column_name, com.comments
                    FROM user_tab_columns c
                    LEFT JOIN user_col_comments com ON c.table_name = com.table_name AND c.column_name = com.column_name
                    WHERE c.table_name = ?
                    ORDER BY c.column_id
                ", [$tableName]);
                foreach ($results as $row) { 
                    $columns[] = [
                        'name' => $row->column_name,
                        'label' => $row->comments ?: $row->column_name
                    ]; 
                }
            } elseif ($driver === 'pgsql') {
                 // Postgres uses col_description
                 $results = $connection->select("
                    SELECT column_name, 
                           (SELECT pg_catalog.col_description(c.oid, cols.ordinal_position::int)
                            FROM pg_catalog.pg_class c
                            WHERE c.relname = cols.table_name
                            AND c.relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname = cols.table_schema)) as label
                    FROM information_schema.columns cols
                    WHERE table_schema = 'public' AND table_name = ?
                    ORDER BY ordinal_position
                ", [$tableName]);
                 foreach ($results as $row) { 
                     $columns[] = [
                         'name' => $row->column_name,
                         'label' => $row->label ?: $row->column_name
                     ]; 
                 }
            } elseif ($driver === 'sqlsrv') {
                 $results = $connection->select("
                    SELECT COLUMN_NAME,
                           (SELECT value FROM sys.extended_properties 
                            WHERE major_id = OBJECT_ID(?) AND minor_id = ORDINAL_POSITION AND name = 'MS_Description') as label
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = ?
                    ORDER BY ORDINAL_POSITION
                ", [$tableName, $tableName]);
                foreach ($results as $row) { 
                    $columns[] = [
                        'name' => $row->COLUMN_NAME,
                        'label' => $row->label ?: $row->COLUMN_NAME
                    ]; 
                }
            }

            return $this->sendResponse($columns, 'Columns retrieved successfully.');

        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch columns: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/data-sources/{dataSource}/test",
     *     tags={"Data Sources"},
     *     summary="Test connection for a data source",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Connection test results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function testConnection(DataSource $data_source = null): JsonResponse
    {
        try {
            $type = request('type');
            $config = request('connection_config');

            if (!$config && $data_source) {
                $type = $data_source->type;
                $config = $data_source->connection_config;
            }

            if (!$type || !$config) {
                return $this->sendError('Missing configuration data.');
            }

            $result = $this->performConnectionTest($type, $config);

            if ($result['success']) {
                return $this->sendResponse([], 'Connection successful.');
            } else {
                return $this->sendError('Connection failed: ' . $result['message'], [], 200);
            }

        } catch (\Exception $e) {
            return $this->sendError('Test failed: ' . $e->getMessage(), [], 200);
        }
    }

    /**
     * Internal connection tester logic
     */
    /**
     * Internal helper to configure a runtime DB connection
     */
    private function configureRuntimeConnection(array $config, string $type, string $connectionName): string
    {
        $driverMap = [
            'oracle' => 'oracle',
            'postgres' => 'pgsql',
            'mysql' => 'mysql',
            'mssql' => 'sqlsrv',
        ];
        
        $driver = $driverMap[$type] ?? $type;

        $dbConfig = [
            'driver' => $driver,
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? ($driver === 'mysql' ? '3306' : ($driver === 'pgsql' ? '5432' : ($driver === 'sqlsrv' ? '1433' : '1521'))),
            'database' => $config['database'] ?? '',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ],
        ];

        if ($driver === 'oracle') {
             $service = $config['service_name'] ?? $config['sid'] ?? $config['database'] ?? '';
             $dbConfig['service_name'] = $service;
             $dbConfig['database'] = ''; 
             $dbConfig['charset'] = 'AL32UTF8';
        }

        if ($driver === 'sqlsrv') {
            $dbConfig['charset'] = 'utf8';
            $dbConfig['prefix_indexes'] = true;
        }

        \Illuminate\Support\Facades\Config::set("database.connections.{$connectionName}", $dbConfig);
        \Illuminate\Support\Facades\DB::purge($connectionName);

        return $driver;
    }

    /**
     * Internal connection tester logic
     */
    private function performConnectionTest(string $type, array $config): array
    {
        $connectionName = 'test_runtime_' . uniqid();
        
        try {
            $driver = $this->configureRuntimeConnection($config, $type, $connectionName);

            $connection = \Illuminate\Support\Facades\DB::connection($connectionName);
            
            // Force connection attempt
            if ($driver === 'oracle') {
                $connection->select("SELECT 1 FROM DUAL");
            } else {
                $connection->select("SELECT 1");
            }

            return ['success' => true, 'message' => 'OK'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            \Illuminate\Support\Facades\DB::disconnect($connectionName);
        }
    }
}
