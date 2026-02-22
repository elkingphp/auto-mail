<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends BaseController
{
    public function index(): JsonResponse
    {
        return $this->sendResponse(\App\Http\Resources\DepartmentResource::collection(Department::all()), 'Departments retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:departments,code',
        ]);

        $department = Department::create($data);

        return $this->sendResponse(new \App\Http\Resources\DepartmentResource($department), 'Department created successfully.', 201);
    }

    public function show(Department $department): JsonResponse
    {
        return $this->sendResponse(new \App\Http\Resources\DepartmentResource($department->load('users')), 'Department retrieved successfully.');
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:departments,code,' . $department->id,
        ]);

        $department->update($data);

        return $this->sendResponse(new \App\Http\Resources\DepartmentResource($department), 'Department updated successfully.');
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();
        return $this->sendResponse([], 'Department deleted successfully.', 204);
    }
}
