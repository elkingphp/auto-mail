<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="UpdateReportFilterRequest",
 *      @OA\Property(property="label", type="string"),
 *      @OA\Property(property="variable_name", type="string"),
 *      @OA\Property(property="filter_type", type="string"),
 *      @OA\Property(property="is_required", type="boolean"),
 *      @OA\Property(property="default_value", type="string"),
 *      @OA\Property(property="order_position", type="integer")
 * )
 */
class UpdateReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'sometimes|exists:reports,id',
            'label' => 'sometimes|string|max:255',
            'variable_name' => 'sometimes|string|max:255',
            'filter_type' => 'sometimes|string|in:text,number,date,date_range,select',
            'is_required' => 'boolean',
            'default_value' => 'nullable|string',
            'order_position' => 'integer',
        ];
    }
}
