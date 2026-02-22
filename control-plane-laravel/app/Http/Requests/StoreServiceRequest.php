<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="StoreServiceRequest",
 *      required={"name"},
 *      @OA\Property(property="name", type="string", example="HR Service"),
 *      @OA\Property(property="description", type="string", example="Human Resources related reports")
 * )
 */
class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
        ];
    }
}
