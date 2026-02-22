<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ScheduleResource",
 *     title="Schedule Resource",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="report_id", type="string", format="uuid"),
 *     @OA\Property(property="frequency", type="string"),
 *     @OA\Property(property="time", type="string"),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'report' => $this->whenLoaded('report'),
            'frequency' => $this->frequency,
            'time' => $this->time,
            'is_active' => $this->is_active,
            'delivery_mode' => $this->delivery_mode,
            'email_server_id' => $this->email_server_id,
            'email_server' => $this->whenLoaded('emailServer'),
            'email_template_id' => $this->email_template_id,
            'email_template' => $this->whenLoaded('emailTemplate'),
            'recipients' => $this->recipients,
            'parameters' => $this->parameters,
            'frequency_options' => $this->frequency_options,
            'ftp_servers' => $this->whenLoaded('ftpServers'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
