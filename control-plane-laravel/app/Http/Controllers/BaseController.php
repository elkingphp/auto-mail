<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="RBDB Control Plane API",
 *      description="API documentation for Report Builder From Database Control Plane",
 *      @OA\Contact(
 *          email="support@rbdb.local"
 *      )
 * )
 *
 * @OA\Server(
 *      url="/api/v1",
 *      description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */
class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @param mixed $result
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public function sendResponse(mixed $result, string $message, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
            'error'   => null,
        ];

        return response()->json($response, $code);
    }

    /**
     * return error response.
     *
     * @param string $error
     * @param array $errorMessages
     * @param int $code
     * @return JsonResponse
     */
    public function sendError(string $error, array $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
            'data'    => null,
            'error'   => !empty($errorMessages) ? $errorMessages : $error,
        ];

        return response()->json($response, $code);
    }
}
