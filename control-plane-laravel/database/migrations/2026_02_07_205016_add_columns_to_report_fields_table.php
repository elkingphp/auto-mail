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
        Schema::table('report_fields', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true);
            $table->integer('order_position')->default(0);
            $table->string('data_type')->default('string');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_fields', function (Blueprint $table) {
            $table->dropColumn(['is_visible', 'order_position', 'data_type']);
        });
    }
};
