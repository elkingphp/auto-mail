<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'report' => new ReportResource($this->whenLoaded('report')),
            'status' => $this->status,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'output_path' => $this->output_path,
            'file_size' => $this->file_size,
            'error_log' => $this->error_log,
            'triggered_by' => $this->triggered_by,
            'triggered_by_user' => new UserResource($this->whenLoaded('triggeredByUser')),
            'notification_emails' => $this->notification_emails,
            'parameters' => $this->parameters,
            'ftp_path' => $this->ftp_path,
            'email_sent_at' => $this->email_sent_at,
            'email_status' => $this->email_status,
            'created_at' => $this->created_at,
        ];
    }
}
