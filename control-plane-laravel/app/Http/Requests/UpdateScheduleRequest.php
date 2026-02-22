<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="UpdateScheduleRequest",
 *      @OA\Property(property="frequency", type="string"),
 *      @OA\Property(property="time", type="string"),
 *      @OA\Property(property="is_active", type="boolean")
 * )
 */
class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'sometimes|exists:reports,id',
            'frequency' => 'sometimes|required|string|max:255',
            'time' => 'sometimes|required|date_format:H:i:s',
            'is_active' => 'boolean',
            'email_server_id' => 'nullable|exists:email_servers,id',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'delivery_mode' => 'nullable|string|in:email,ftp,email_and_ftp,both,none',
            'ftp_server_ids' => 'nullable|array',
            'ftp_server_ids.*' => 'exists:ftp_servers,id',
            'recipients' => 'nullable|string',
            'parameters' => 'nullable|array',
            'frequency_options' => 'nullable|array',
            'start_date' => 'nullable|date',
            'start_hour' => 'nullable|string',
        ];
    }
}
