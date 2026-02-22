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
            if (!Schema::hasColumn('executions', 'otp_used_at')) {
                $table->timestamp('otp_used_at')->nullable();
            }
            if (!Schema::hasColumn('executions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
        });

        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'retention_period')) {
                $table->string('retention_period')->default('24h')->after('retention_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropColumn(['otp_used_at', 'expires_at']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('retention_period');
        });
    }
};
