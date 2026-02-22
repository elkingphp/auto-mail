<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReportFilter extends Model
{
    use HasUuids;

    protected $fillable = ['report_id', 'label', 'variable_name', 'filter_type', 'is_required', 'default_value', 'order_position'];
    
    protected $casts = [
        'is_required' => 'boolean'
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
