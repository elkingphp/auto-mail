<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="StoreReportRequest",
 *      required={"service_id", "data_source_id", "name", "type"},
 *      @OA\Property(property="service_id", type="string", format="uuid"),
 *      @OA\Property(property="data_source_id", type="string", format="uuid"),
 *      @OA\Property(property="name", type="string"),
 *      @OA\Property(property="type", type="string", enum={"sql", "visual", "service"}),
 *      @OA\Property(property="sql_definition", type="string"),
 *      @OA\Property(property="description", type="string")
 * )
 */
class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => 'required|exists:services,id',
            'data_source_id' => 'required|exists:data_sources,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:sql,visual,service',
            'sql_definition' => 'nullable|string',
            'visual_definition' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'retention_days' => 'sometimes|integer|min:0',
            'schedule_frequency' => 'nullable|string|in:hourly,daily,monthly,quarterly,semiannually,yearly',
            'delivery_mode' => 'nullable|string|in:email,ftp,both',
            'email_server_id' => 'nullable|exists:email_servers,id',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'ftp_server_id' => 'nullable|exists:ftp_servers,id',
            'default_recipients' => 'nullable|string',
            'fields' => 'nullable|array',
            'fields.*.source_field' => 'required|string',
            'fields.*.alias' => 'nullable|string',
            'fields.*.is_visible' => 'boolean',
            'fields.*.order_position' => 'integer',
            'fields.*.format' => 'nullable|string',
        ];

    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->type === 'sql' && $this->sql_definition) {
                try {
                    $sqlValidator = new \App\Services\SqlValidatorService();
                    $sqlValidator->validateSql($this->sql_definition);
                } catch (\Exception $e) {
                    $validator->errors()->add('sql_definition', $e->getMessage());
                }
            }
        });
    }
}
