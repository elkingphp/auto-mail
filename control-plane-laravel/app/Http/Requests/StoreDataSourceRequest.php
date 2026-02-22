<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *      schema="StoreDataSourceRequest",
 *      required={"name", "type", "connection_config"},
 *      @OA\Property(property="name", type="string", example="Main Oracle DB"),
 *      @OA\Property(property="type", type="string", enum={"oracle", "mysql", "postgres", "mssql"}),
 *      @OA\Property(property="connection_config", type="string", example="{host: 'localhost'...}")
 * )
 */
class StoreDataSourceRequest extends FormRequest
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
