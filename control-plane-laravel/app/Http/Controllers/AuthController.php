<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="Login to the system",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@rbdb.local"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="name", type="string")
     *             ),
     *             @OA\Property(property="message", type="string", example="User login successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private \App\Services\AuditService $auditService;

    public function __construct(\App\Services\AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function login(Request $request)
    {
        try {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                /** @var User $user */
                $user = Auth::user();
                $user->load(['role', 'department']);
                
                $success['token'] = $user->createToken('RBDB-Auth')->plainTextToken;
                $success['user'] = new \App\Http\Resources\UserResource($user);

                $this->auditService->log('login', 'user', $user->id);

                return $this->sendResponse($success, 'User login successfully.');
            } else {
                $this->auditService->log('login_failed', 'user', null, ['email' => $request->email]);
                return $this->sendError('Unauthorized.', ['error' => 'Invalid credentials'], 401);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Login Error: " . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Internal Server Error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout from the system",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout Successful"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $this->auditService->log('logout', 'user', $request->user()->id);
        $request->user()->currentAccessToken()->delete();
        return $this->sendResponse([], 'User logout successfully.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Authentication"},
     *     summary="Get current user info",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User Details"
     *     )
     * )
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['role', 'department']);
        return $this->sendResponse(new \App\Http\Resources\UserResource($user), 'User details retrieved successfully.');
    }
}
