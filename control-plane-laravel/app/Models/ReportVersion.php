<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReportVersion extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'version_number',
        'type',
        'definition',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'definition' => 'array',
        'created_at' => 'datetime'
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
