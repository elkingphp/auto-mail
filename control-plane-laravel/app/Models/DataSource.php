<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'type', 'connection_config'];

    protected $casts = [
        'connection_config' => 'encrypted:array',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
