<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {
        return $this->sendResponse(\App\Http\Resources\UserResource::collection(User::with('role')->get()), 'Users retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        $user = User::create($validated);

        return $this->sendResponse(new \App\Http\Resources\UserResource($user->load('role')), 'User created successfully.', 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:8',
            'role_id' => 'sometimes|required|exists:roles,id',
        ]);

        if (isset($validated['password']) && $validated['password']) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return $this->sendResponse(new \App\Http\Resources\UserResource($user->load('role')), 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->sendResponse([], 'User deleted successfully.', 204);
    }

    /**
     * Get user notifications.
     */
    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()->limit(20)->get();
        return $this->sendResponse($notifications, 'Notifications retrieved successfully.');
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return $this->sendResponse([], 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return $this->sendResponse([], 'All notifications marked as read.');
    }

    /**
     * Clear all notifications.
     */
    public function clearAllNotifications(Request $request)
    {
        $request->user()->notifications()->delete();
        return $this->sendResponse([], 'All notifications cleared.');
    }
}
