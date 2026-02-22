<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="StoreReportFieldRequest",
 *      required={"report_id", "source_field", "alias"},
 *      @OA\Property(property="report_id", type="string", format="uuid"),
 *      @OA\Property(property="source_field", type="string", example="first_name"),
 *      @OA\Property(property="alias", type="string", example="First Name"),
 *      @OA\Property(property="description", type="string"),
 *      @OA\Property(property="filter_type", type="string", example="text"),
 *      @OA\Property(property="is_visible", type="boolean", example=true),
 *      @OA\Property(property="order_position", type="integer", example=1),
 *      @OA\Property(property="data_type", type="string", example="string")
 * )
 */
class StoreReportFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'required|exists:reports,id',
            'source_field' => 'required|string|max:255',
            'alias' => 'required|string|max:255',
            'description' => 'nullable|string',
            'filter_type' => 'nullable|string',
            'is_visible' => 'sometimes|boolean',
            'order_position' => 'sometimes|integer',
            'data_type' => 'nullable|string|in:string,number,date,boolean',
        ];
    }
}
