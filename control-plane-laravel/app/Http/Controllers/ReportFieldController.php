<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportFieldRequest;
use App\Http\Requests\UpdateReportFieldRequest;
use App\Http\Resources\ReportFieldResource;
use App\Models\ReportField;
use App\Services\ReportFieldService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class ReportFieldController extends BaseController
{
    private ReportFieldService $service;

    public function __construct(ReportFieldService $service)
    {
        $this->service = $service;
        $this->authorizeResource(ReportField::class, 'report_field');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/report-fields",
     *     tags={"Report Fields"},
     *     summary="List all report fields",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ReportFieldResource")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $reportId = request('report_id');
        $fields = $this->service->getAll($reportId);
        return $this->sendResponse(ReportFieldResource::collection($fields), 'Report Fields retrieved successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/report-fields",
     *     tags={"Report Fields"},
     *     summary="Create a new report field",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreReportFieldRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Report Field created",
     *         @OA\JsonContent(ref="#/components/schemas/ReportFieldResource")
     *     )
     * )
     */
    public function store(StoreReportFieldRequest $request): JsonResponse
    {
        $field = $this->service->create($request->validated());
        return $this->sendResponse(new ReportFieldResource($field), 'Report Field created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/report-fields/{reportField}",
     *     tags={"Report Fields"},
     *     summary="Get a report field by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reportField",
     *         in="path",
     *         description="ReportField UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/ReportFieldResource")
     *     )
     * )
     */
    public function show(ReportField $reportField): JsonResponse
    {
        return $this->sendResponse(new ReportFieldResource($reportField), 'Report Field retrieved successfully.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/report-fields/{reportField}",
     *     tags={"Report Fields"},
     *     summary="Update a report field",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reportField",
     *         in="path",
     *         description="ReportField UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateReportFieldRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report Field updated",
     *         @OA\JsonContent(ref="#/components/schemas/ReportFieldResource")
     *     )
     * )
     */
    public function update(UpdateReportFieldRequest $request, ReportField $reportField): JsonResponse
    {
        $updatedField = $this->service->update($reportField, $request->validated());
        return $this->sendResponse(new ReportFieldResource($updatedField), 'Report Field updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/report-fields/{reportField}",
     *     tags={"Report Fields"},
     *     summary="Delete a report field",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reportField",
     *         in="path",
     *         description="ReportField UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Report Field deleted"
     *     )
     * )
     */
    public function destroy(ReportField $reportField): JsonResponse
    {
        $this->service->delete($reportField);
        return $this->sendResponse([], 'Report Field deleted successfully.', 204);
    }
}
