<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryTargetRequest;
use App\Http\Requests\UpdateDeliveryTargetRequest;
use App\Http\Resources\DeliveryTargetResource;
use App\Models\DeliveryTarget;
use App\Services\DeliveryTargetService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class DeliveryTargetController extends BaseController
{
    private DeliveryTargetService $service;

    public function __construct(DeliveryTargetService $service)
    {
        $this->service = $service;
        $this->authorizeResource(DeliveryTarget::class, 'delivery_target');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/delivery-targets",
     *     tags={"Delivery Targets"},
     *     summary="List all delivery targets",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/DeliveryTargetResource")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $targets = $this->service->getAll();
        return $this->sendResponse(DeliveryTargetResource::collection($targets), 'Delivery Targets retrieved successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/delivery-targets",
     *     tags={"Delivery Targets"},
     *     summary="Create a new delivery target",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreDeliveryTargetRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Delivery Target created",
     *         @OA\JsonContent(ref="#/components/schemas/DeliveryTargetResource")
     *     )
     * )
     */
    public function store(StoreDeliveryTargetRequest $request): JsonResponse
    {
        $target = $this->service->create($request->validated());
        return $this->sendResponse(new DeliveryTargetResource($target), 'Delivery Target created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/delivery-targets/{deliveryTarget}",
     *     tags={"Delivery Targets"},
     *     summary="Get a delivery target by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="deliveryTarget",
     *         in="path",
     *         description="DeliveryTarget UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DeliveryTargetResource")
     *     )
     * )
     */
    public function show(DeliveryTarget $deliveryTarget): JsonResponse
    {
        return $this->sendResponse(new DeliveryTargetResource($deliveryTarget), 'Delivery Target retrieved successfully.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/delivery-targets/{deliveryTarget}",
     *     tags={"Delivery Targets"},
     *     summary="Update a delivery target",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="deliveryTarget",
     *         in="path",
     *         description="DeliveryTarget UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateDeliveryTargetRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Delivery Target updated",
     *         @OA\JsonContent(ref="#/components/schemas/DeliveryTargetResource")
     *     )
     * )
     */
    public function update(UpdateDeliveryTargetRequest $request, DeliveryTarget $deliveryTarget): JsonResponse
    {
        $updatedTarget = $this->service->update($deliveryTarget, $request->validated());
        return $this->sendResponse(new DeliveryTargetResource($updatedTarget), 'Delivery Target updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/delivery-targets/{deliveryTarget}",
     *     tags={"Delivery Targets"},
     *     summary="Delete a delivery target",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="deliveryTarget",
     *         in="path",
     *         description="DeliveryTarget UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Delivery Target deleted"
     *     )
     * )
     */
    public function destroy(DeliveryTarget $deliveryTarget): JsonResponse
    {
        $this->service->delete($deliveryTarget);
        return $this->sendResponse([], 'Delivery Target deleted successfully.', 204);
    }
}
