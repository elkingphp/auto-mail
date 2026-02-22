<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="UpdateDataSourceRequest",
 *      @OA\Property(property="name", type="string", example="Main Oracle DB Updated"),
 *      @OA\Property(property="type", type="string", enum={"oracle", "mysql", "postgres", "mssql"}),
 *      @OA\Property(property="connection_config", type="string", example="New Config")
 * )
 */
class UpdateDataSourceRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:oracle,mysql,postgres,mssql',
            'connection_config' => 'required|array',
        ];
    }
}
