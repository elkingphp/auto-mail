<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ServiceController extends BaseController
{
    private ServiceService $service;
    private \App\Services\AuditService $auditService;

    public function __construct(ServiceService $service, \App\Services\AuditService $auditService)
    {
        $this->service = $service;
        $this->auditService = $auditService;
        $this->authorizeResource(Service::class, 'service');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/services",
     *     tags={"Services"},
     *     summary="List all services",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ServiceResource")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $services = $this->service->getAll();
        return $this->sendResponse(ServiceResource::collection($services), 'Services retrieved successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/services",
     *     tags={"Services"},
     *     summary="Create a new service",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreServiceRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service created",
     *         @OA\JsonContent(ref="#/components/schemas/ServiceResource")
     *     )
     * )
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->service->create($request->validated());
        $this->auditService->logCreate('service', $service->id, $service->toArray());
        return $this->sendResponse(new ServiceResource($service), 'Service created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/services/{service}",
     *     tags={"Services"},
     *     summary="Get a service by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         description="Service UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/ServiceResource")
     *     ),
     *     @OA\Response(response=404, description="Service not found")
     * )
     */
    public function show(Service $service): JsonResponse
    {
        return $this->sendResponse(new ServiceResource($service), 'Service retrieved successfully.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/services/{service}",
     *     tags={"Services"},
     *     summary="Update a service",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         description="Service UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateServiceRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service updated",
     *         @OA\JsonContent(ref="#/components/schemas/ServiceResource")
     *     )
     * )
     */
    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $oldValues = $service->toArray();
        $updatedService = $this->service->update($service, $request->validated());
        $this->auditService->logUpdate('service', $updatedService->id, $oldValues, $updatedService->fresh()->toArray());
        return $this->sendResponse(new ServiceResource($updatedService), 'Service updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/services/{service}",
     *     tags={"Services"},
     *     summary="Delete a service",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         description="Service UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=204, description="Service deleted")
     * )
     */
    public function destroy(Service $service): JsonResponse
    {
        $oldValues = $service->toArray();
        $this->service->delete($service);
        $this->auditService->logDelete('service', $service->id, $oldValues);
        return $this->sendResponse([], 'Service deleted successfully.', 204);
    }
}
