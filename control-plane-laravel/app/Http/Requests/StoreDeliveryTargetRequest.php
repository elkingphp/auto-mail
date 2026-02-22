<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="StoreDeliveryTargetRequest",
 *      required={"report_id", "type", "config"},
 *      @OA\Property(property="report_id", type="string", format="uuid"),
 *      @OA\Property(property="type", type="string", enum={"email", "ftp"}),
 *      @OA\Property(property="config", type="string", example="{to: 'user@example.com'}")
 * )
 */
class StoreDeliveryTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'required|exists:reports,id',
            'type' => 'required|string|in:email,ftp',
            'config' => 'required|array', // Model cast expects array but controller validation might need json string if passed differently, but standard laravel validation handles arrays. But for swagger example I put string/json.
        ];
    }
}
