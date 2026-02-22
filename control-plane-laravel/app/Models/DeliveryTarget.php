<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeliveryTarget extends Model
{
    use HasUuids;

    protected $fillable = ['report_id', 'type', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
