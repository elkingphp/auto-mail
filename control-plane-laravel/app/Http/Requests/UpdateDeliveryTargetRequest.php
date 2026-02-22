<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="UpdateDeliveryTargetRequest",
 *      @OA\Property(property="type", type="string", enum={"email", "ftp"}),
 *      @OA\Property(property="config", type="string")
 * )
 */
class UpdateDeliveryTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'sometimes|exists:reports,id',
            'type' => 'sometimes|required|string|in:email,ftp',
            'config' => 'sometimes|required|array',
        ];
    }
}
