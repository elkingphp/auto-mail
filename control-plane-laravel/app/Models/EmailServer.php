<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmailServer extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'driver',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'is_active',
        'status',
        'last_check_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'encrypted',
        'port' => 'integer',
        'last_check_at' => 'datetime',
    ];
}
