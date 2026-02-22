<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="StoreScheduleRequest",
 *      required={"report_id", "frequency", "time"},
 *      @OA\Property(property="report_id", type="string", format="uuid"),
 *      @OA\Property(property="frequency", type="string", example="daily"),
 *      @OA\Property(property="time", type="string", example="08:00:00"),
 *      @OA\Property(property="is_active", type="boolean", example=true)
 * )
 */
class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => 'required|exists:reports,id',
            'frequency' => 'required|string|max:255',
            'time' => 'required|date_format:H:i:s',
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
