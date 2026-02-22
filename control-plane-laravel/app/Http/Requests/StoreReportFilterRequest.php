<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="StoreReportFilterRequest",
 *      required={"report_id", "label", "variable_name", "filter_type"},
 *      @OA\Property(property="report_id", type="string", format="uuid"),
 *      @OA\Property(property="label", type="string", example="Start Date"),
 *      @OA\Property(property="variable_name", type="string", example="p_start"),
 *      @OA\Property(property="filter_type", type="string", example="date"),
 *      @OA\Property(property="is_required", type="boolean"),
 *      @OA\Property(property="default_value", type="string"),
 *      @OA\Property(property="order_position", type="integer")
 * )
 */
class StoreReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'required|exists:reports,id',
            'label' => 'required|string|max:255',
            'variable_name' => 'required|string|max:255',
            'filter_type' => 'required|string|in:text,number,date,date_range,select',
            'is_required' => 'boolean',
            'default_value' => 'nullable|string',
            'order_position' => 'nullable|integer',
        ];
    }
}
