<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReportField extends Model
{
    use HasUuids;

    protected $fillable = ['report_id', 'source_field', 'alias', 'description', 'filter_type', 'is_visible', 'order_position', 'data_type', 'format'];


    protected $casts = [
        'is_visible' => 'boolean',
        'order_position' => 'integer'
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
