<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FtpServer extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'root_path',
        'passive_mode',
        'is_active',
        'status',
        'last_check_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'passive_mode' => 'boolean',
        'password' => 'encrypted',
        'port' => 'integer',
        'last_check_at' => 'datetime',
    ];
}
