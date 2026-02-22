<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="UpdateReportFieldRequest",
 *      @OA\Property(property="source_field", type="string"),
 *      @OA\Property(property="alias", type="string"),
 *      @OA\Property(property="description", type="string"),
 *      @OA\Property(property="filter_type", type="string"),
 *      @OA\Property(property="is_visible", type="boolean"),
 *      @OA\Property(property="order_position", type="integer"),
 *      @OA\Property(property="data_type", type="string")
 * )
 */
class UpdateReportFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'sometimes|exists:reports,id',
            'source_field' => 'sometimes|required|string|max:255',
            'alias' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'filter_type' => 'nullable|string',
            'is_visible' => 'sometimes|boolean',
            'order_position' => 'sometimes|integer',
            'data_type' => 'nullable|string|in:string,number,date,boolean',
        ];
    }
}
