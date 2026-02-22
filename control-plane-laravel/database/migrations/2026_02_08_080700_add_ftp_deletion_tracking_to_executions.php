<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            if (!Schema::hasColumn('executions', 'ftp_deleted_at')) {
                $table->timestamp('ftp_deleted_at')->nullable();
            }
            if (!Schema::hasColumn('executions', 'ftp_delete_status')) {
                $table->string('ftp_delete_status')->nullable(); // success, failed
            }
        });
    }

    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropColumn(['ftp_deleted_at', 'ftp_delete_status']);
        });
    }
};
