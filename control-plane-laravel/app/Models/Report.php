<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasUuids;

    protected $fillable = [
        'service_id',
        'data_source_id',
        'department_id',
        'name',
        'type',
        'sql_definition',
        'visual_definition',
        'description',
        'created_by',
        'is_active',
        'retention_days',
        'schedule_frequency',
        'delivery_mode',
        'email_server_id',
        'email_template_id',
        'ftp_server_id',
        'default_recipients',
        'timeout_seconds',
        'is_critical'
    ];

    protected $casts = [
        'visual_definition' => 'array',
        'is_active' => 'boolean',
        'is_critical' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fields()
    {
        return $this->hasMany(ReportField::class);
    }

    public function filters()
    {
        return $this->hasMany(ReportFilter::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function executions()
    {
        return $this->hasMany(Execution::class);
    }

    public function deliveryTargets()
    {
        return $this->hasMany(DeliveryTarget::class);
    }

    public function emailServer()
    {
        return $this->belongsTo(EmailServer::class);
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function ftpServer()
    {
        return $this->belongsTo(FtpServer::class);
    }
}
