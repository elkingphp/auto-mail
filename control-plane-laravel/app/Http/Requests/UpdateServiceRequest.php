<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="UpdateServiceRequest",
 *      @OA\Property(property="name", type="string", example="HR Service Update"),
 *      @OA\Property(property="description", type="string", example="Updated Description")
 * )
 */
class UpdateServiceRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:services,name,' . $this->service->id,
            'description' => 'nullable|string',
        ];
    }
}
