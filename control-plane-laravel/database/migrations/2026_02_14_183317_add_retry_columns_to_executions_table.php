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
            $table->integer('retry_count')->default(0)->after('status');
            $table->integer('max_retries')->default(3)->after('retry_count');
            $table->string('priority')->default('medium')->after('max_retries');
            $table->timestamp('last_retry_at')->nullable()->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'max_retries', 'priority', 'last_retry_at']);
        });
    }
};
