<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ReportFieldResource",
 *     title="ReportField Resource",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="report_id", type="string", format="uuid"),
 *     @OA\Property(property="source_field", type="string"),
 *     @OA\Property(property="alias", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="filter_type", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class ReportFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'source_field' => $this->source_field,
            'alias' => $this->alias,
            'description' => $this->description,
            'filter_type' => $this->filter_type,
            'is_visible' => (bool)$this->is_visible,
            'order_position' => (int)$this->order_position,
            'data_type' => $this->data_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
