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
            $table->json('parameters')->nullable();
        });
        
        Schema::table('schedules', function (Blueprint $table) {
            $table->json('parameters')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropColumn('parameters');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('parameters');
        });
    }
};
