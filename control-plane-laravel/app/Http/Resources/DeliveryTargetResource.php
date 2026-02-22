<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="DeliveryTargetResource",
 *     title="DeliveryTarget Resource",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="report_id", type="string", format="uuid"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="config", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class DeliveryTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'type' => $this->type,
            'config' => $this->config, // Auto cast to array
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
