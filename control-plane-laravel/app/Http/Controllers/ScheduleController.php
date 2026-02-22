<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class ScheduleController extends BaseController
{
    private ScheduleService $service;

    public function __construct(ScheduleService $service)
    {
        $this->service = $service;
        $this->authorizeResource(Schedule::class, 'schedule');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/schedules",
     *     tags={"Schedules"},
     *     summary="List all schedules",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ScheduleResource")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $query = Schedule::with(['report', 'emailServer', 'emailTemplate', 'ftpServers']);
        
        if (request()->has('report_id')) {
            $query->where('report_id', request('report_id'));
        }

        $schedules = $query->get();
        return $this->sendResponse(ScheduleResource::collection($schedules), 'Schedules retrieved successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/schedules",
     *     tags={"Schedules"},
     *     summary="Create a new schedule",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreScheduleRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Schedule created",
     *         @OA\JsonContent(ref="#/components/schemas/ScheduleResource")
     *     )
     * )
     */
    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $schedule = $this->service->create($request->validated());
        return $this->sendResponse(new ScheduleResource($schedule), 'Schedule created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/schedules/{schedule}",
     *     tags={"Schedules"},
     *     summary="Get a schedule by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="schedule",
     *         in="path",
     *         description="Schedule UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/ScheduleResource")
     *     )
     * )
     */
    public function show(Schedule $schedule): JsonResponse
    {
        $schedule->load(['report', 'emailServer', 'emailTemplate', 'ftpServers']);
        return $this->sendResponse(new ScheduleResource($schedule), 'Schedule retrieved successfully.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/schedules/{schedule}",
     *     tags={"Schedules"},
     *     summary="Update a schedule",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="schedule",
     *         in="path",
     *         description="Schedule UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateScheduleRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Schedule updated",
     *         @OA\JsonContent(ref="#/components/schemas/ScheduleResource")
     *     )
     * )
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        $updatedSchedule = $this->service->update($schedule, $request->validated());
        $updatedSchedule->load(['report', 'emailServer', 'emailTemplate', 'ftpServers']);
        return $this->sendResponse(new ScheduleResource($updatedSchedule), 'Schedule updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/schedules/{schedule}",
     *     tags={"Schedules"},
     *     summary="Delete a schedule",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="schedule",
     *         in="path",
     *         description="Schedule UUID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Schedule deleted"
     *     )
     * )
     */
    public function destroy(Schedule $schedule): JsonResponse
    {
        $this->service->delete($schedule);
        return $this->sendResponse([], 'Schedule deleted successfully.', 204);
    }
}
