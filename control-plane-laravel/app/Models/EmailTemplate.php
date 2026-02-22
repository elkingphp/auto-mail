<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'is_default',
        'is_active',
        'require_otp',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'require_otp' => 'boolean',
        'variables' => 'array',
    ];
}
