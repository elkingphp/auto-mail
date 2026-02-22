<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ReportResource",
 *     title="Report Resource",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="sql_definition", type="string"),
 *     @OA\Property(property="service", ref="#/components/schemas/ServiceResource"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'data_source_id' => $this->data_source_id,
            'data_source' => new DataSourceResource($this->whenLoaded('dataSource')),
            'department_id' => $this->department_id,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'name' => $this->name,
            'type' => $this->type,
            'sql_definition' => $this->sql_definition,
            'visual_definition' => $this->visual_definition,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'retention_days' => $this->retention_days,
            'schedule_frequency' => $this->schedule_frequency,
            'created_by' => $this->created_by,
            'delivery_mode' => $this->delivery_mode,
            'email_server_id' => $this->email_server_id,
            'email_server' => $this->emailServer ? [
                'id' => $this->emailServer->id,
                'name' => $this->emailServer->name,
                'connection_config' => [
                    'host' => $this->emailServer->host,
                    'port' => $this->emailServer->port,
                    'username' => $this->emailServer->username,
                    'password' => $this->emailServer->password,
                ]
            ] : null,
            'email_template_id' => $this->email_template_id,
            'email_template' => $this->emailTemplate,
            'ftp_server_id' => $this->ftp_server_id,
            'ftp_server' => $this->ftpServer ? [
                'id' => $this->ftpServer->id,
                'name' => $this->ftpServer->name,
                'connection_config' => [
                    'host' => $this->ftpServer->host,
                    'port' => $this->ftpServer->port,
                    'username' => $this->ftpServer->username,
                    'password' => $this->ftpServer->password,
                    'root_path' => $this->ftpServer->root_path,
                ]
            ] : null,
            'default_recipients' => $this->default_recipients,
            'retention_period' => $this->retention_period,
            'fields' => $this->fields, // Added fields for Egress Schema
            'filters' => $this->whenLoaded('filters'),
            'schedules' => $this->whenLoaded('schedules'),
            'delivery_targets' => $this->whenLoaded('deliveryTargets'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

    }
}
