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
        Schema::table('executions', function (Blueprint $table) {
            if (!Schema::hasColumn('executions', 'ftp_server_id')) {
                $table->foreignUuid('ftp_server_id')->nullable()->constrained('ftp_servers')->nullOnDelete();
            }
            if (!Schema::hasColumn('executions', 'ftp_path')) {
                $table->string('ftp_path')->nullable()->after('ftp_server_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropForeign(['ftp_server_id']);
            $table->dropColumn(['ftp_server_id', 'ftp_path']);
        });
    }
};
