<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportFilterRequest;
use App\Http\Requests\UpdateReportFilterRequest;
use App\Http\Resources\ReportFilterResource;
use App\Models\ReportFilter;
use App\Services\ReportFilterService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class ReportFilterController extends BaseController
{
    private ReportFilterService $service;

    public function __construct(ReportFilterService $service)
    {
        $this->service = $service;
        $this->authorizeResource(ReportFilter::class, 'report_filter');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/report-filters",
     *     tags={"Report Filters"},
     *     summary="List all report filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ReportFilterResource")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $filters = $this->service->getAll();
        return $this->sendResponse(ReportFilterResource::collection($filters), 'Report Filters retrieved successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/report-filters",
     *     tags={"Report Filters"},
     *     summary="Create a new report filter",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreReportFilterRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Report Filter created",
     *         @OA\JsonContent(ref="#/components/schemas/ReportFilterResource")
     *     )
     * )
     */
    public function store(StoreReportFilterRequest $request): JsonResponse
    {
        $filter = $this->service->create($request->validated());
        return $this->sendResponse(new ReportFilterResource($filter), 'Report Filter created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/report-filters/{reportFilter}",
     *     tags={"Report Filters"},
     *     summary="Get a report filter by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reportFilter",
     *         in="path",
     *         description="ReportFilter UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/ReportFilterResource")
     *     )
     * )
     */
    public function show(ReportFilter $reportFilter): JsonResponse
    {
        return $this->sendResponse(new ReportFilterResource($reportFilter), 'Report Filter retrieved successfully.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/report-filters/{reportFilter}",
     *     tags={"Report Filters"},
     *     summary="Update a report filter",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reportFilter",
     *         in="path",
     *         description="ReportFilter UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateReportFilterRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report Filter updated",
     *         @OA\JsonContent(ref="#/components/schemas/ReportFilterResource")
     *     )
     * )
     */
    public function update(UpdateReportFilterRequest $request, ReportFilter $reportFilter): JsonResponse
    {
        $updatedFilter = $this->service->update($reportFilter, $request->validated());
        return $this->sendResponse(new ReportFilterResource($updatedFilter), 'Report Filter updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/report-filters/{reportFilter}",
     *     tags={"Report Filters"},
     *     summary="Delete a report filter",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reportFilter",
     *         in="path",
     *         description="ReportFilter UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Report Filter deleted"
     *     )
     * )
     */
    public function destroy(ReportFilter $reportFilter): JsonResponse
    {
        $this->service->delete($reportFilter);
        return $this->sendResponse([], 'Report Filter deleted successfully.', 204);
    }
}
