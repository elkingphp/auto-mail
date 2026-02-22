<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_id', 
        'frequency', 
        'time', 
        'is_active',
        'email_server_id',
        'email_template_id',
        'delivery_mode',
        'recipients',
        'parameters',
        'frequency_options',
        'start_date',
        'start_hour'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'parameters' => 'array',
        'frequency_options' => 'array'
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function emailServer()
    {
        return $this->belongsTo(EmailServer::class);
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function ftpServers()
    {
        return $this->belongsToMany(FtpServer::class, 'schedule_ftp_servers');
    }
}
