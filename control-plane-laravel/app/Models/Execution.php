<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Execution extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_id',
        'status',
        'started_at',
        'finished_at',
        'output_path',
        'file_size',
        'error_log',
        'delivery_log_json',
        'triggered_by',
        'notification_emails',
        'schedule_id',
        'parameters',
        'otp_code',
        'ftp_server_id',
        'ftp_path',
        'uploaded_at',
        'email_sent_at',
        'email_status',
        'email_failure_reason',
        'last_downloaded_at',
        'download_count',
        'otp_hash',
        'otp_expires_at',
        'otp_validated',
        'otp_used_at',
        'expires_at',
        'deleted_at',
        'ftp_deleted_at',
        'ftp_delete_status',
        'retry_count',
        'max_retries',
        'priority',
        'last_retry_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'parameters' => 'array',
        'notification_emails' => 'array',
        'delivery_log_json' => 'array',
        'uploaded_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'otp_validated' => 'boolean',
        'expires_at' => 'datetime',
        'deleted_at' => 'datetime',
        'ftp_deleted_at' => 'datetime',
        'last_retry_at' => 'datetime',
    ];

    public function triggeredByUser()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
    
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function ftpServer()
    {
        return $this->belongsTo(FtpServer::class);
    }
}
