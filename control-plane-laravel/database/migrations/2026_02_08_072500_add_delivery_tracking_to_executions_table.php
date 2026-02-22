<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            // Only add columns that don't exist yet
            // ftp_server_id and ftp_path already exist from previous migration
            
            // File tracking (file_size already exists)
            if (!Schema::hasColumn('executions', 'uploaded_at')) {
                $table->timestamp('uploaded_at')->nullable();
            }
            
            // Email delivery tracking
            if (!Schema::hasColumn('executions', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable();
            }
            if (!Schema::hasColumn('executions', 'email_status')) {
                $table->string('email_status')->nullable();
            }
            if (!Schema::hasColumn('executions', 'email_failure_reason')) {
                $table->text('email_failure_reason')->nullable();
            }
            
            // Download tracking for retention
            if (!Schema::hasColumn('executions', 'last_downloaded_at')) {
                $table->timestamp('last_downloaded_at')->nullable();
            }
            if (!Schema::hasColumn('executions', 'download_count')) {
                $table->unsignedInteger('download_count')->default(0);
            }
            
            // OTP tracking (otp_code already exists)
            if (!Schema::hasColumn('executions', 'otp_hash')) {
                $table->string('otp_hash')->nullable();
            }
            if (!Schema::hasColumn('executions', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable();
            }
            if (!Schema::hasColumn('executions', 'otp_validated')) {
                $table->boolean('otp_validated')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropForeign(['ftp_server_id']);
            $table->dropColumn([
                'ftp_server_id',
                'ftp_path',
                'file_size',
                'uploaded_at',
                'email_sent_at',
                'email_status',
                'email_failure_reason',
                'last_downloaded_at',
                'download_count',
                'otp_hash',
                'otp_expires_at',
                'otp_validated',
            ]);
        });
    }
};
