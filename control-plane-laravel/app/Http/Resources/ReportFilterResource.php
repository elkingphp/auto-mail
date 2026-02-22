<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ReportFilterResource",
 *     title="ReportFilter Resource",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="report_id", type="string", format="uuid"),
 *     @OA\Property(property="field_id", type="string", format="uuid"),
 *     @OA\Property(property="operator", type="string"),
 *     @OA\Property(property="default_value", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class ReportFilterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'label' => $this->label,
            'variable_name' => $this->variable_name,
            'filter_type' => $this->filter_type,
            'is_required' => (bool)$this->is_required,
            'default_value' => $this->default_value,
            'order_position' => (int)$this->order_position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
