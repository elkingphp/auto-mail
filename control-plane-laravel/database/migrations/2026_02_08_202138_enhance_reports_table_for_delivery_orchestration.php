<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'delivery_mode')) {
                $table->string('delivery_mode')->default('none')->after('description');
            }
            if (!Schema::hasColumn('reports', 'email_server_id')) {
                $table->foreignUuid('email_server_id')->nullable()->constrained('email_servers')->nullOnDelete();
            }
            if (!Schema::hasColumn('reports', 'email_template_id')) {
                $table->foreignUuid('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            }
            if (!Schema::hasColumn('reports', 'ftp_server_id')) {
                $table->foreignUuid('ftp_server_id')->nullable()->constrained('ftp_servers')->nullOnDelete();
            }
            if (!Schema::hasColumn('reports', 'default_recipients')) {
                $table->text('default_recipients')->nullable();
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('schedules', 'start_hour')) {
                $table->time('start_hour')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'start_hour']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['email_server_id']);
            $table->dropForeign(['email_template_id']);
            $table->dropForeign(['ftp_server_id']);
            $table->dropColumn(['delivery_mode', 'email_server_id', 'email_template_id', 'ftp_server_id', 'default_recipients']);
        });
    }
};
