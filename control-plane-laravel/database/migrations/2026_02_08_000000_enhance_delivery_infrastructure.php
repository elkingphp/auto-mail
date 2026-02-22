<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_servers', function (Blueprint $table) {
            $table->string('status')->default('unknown'); // online, offline, unknown
            $table->timestamp('last_check_at')->nullable();
        });

        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->string('status')->default('unknown');
            $table->timestamp('last_check_at')->nullable();
        });

        Schema::table('schedule_ftp_servers', function (Blueprint $table) {
            $table->integer('retention_days')->default(30);
        });
        
        Schema::table('schedules', function (Blueprint $table) {
             // Expanded frequency options if needed, but string 'frequency' can hold 'custom'
             // We might need 'custom_schedule' details
             $table->json('frequency_options')->nullable(); // { "every_hours": 5, "start_time": "08:00" }
        });
    }

    public function down(): void
    {
        Schema::table('email_servers', function (Blueprint $table) {
            $table->dropColumn(['status', 'last_check_at']);
        });

        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->dropColumn(['status', 'last_check_at']);
        });

        Schema::table('schedule_ftp_servers', function (Blueprint $table) {
            $table->dropColumn(['retention_days']);
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('frequency_options');
        });
    }
};
