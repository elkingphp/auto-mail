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
        Schema::table('report_filters', function (Blueprint $table) {
            $table->dropForeign(['field_id']);
            $table->dropColumn(['field_id', 'operator']);
            
            $table->string('label');
            $table->string('variable_name');
            $table->string('filter_type');
            $table->boolean('is_required')->default(false);
            $table->integer('order_position')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_filters', function (Blueprint $table) {
             // Basic restoration attempt, though strict rollback is complex due to data loss
            $table->foreignUuid('field_id')->nullable()->constrained('report_fields')->cascadeOnDelete();
            $table->string('operator')->default('=');
            $table->dropColumn(['label', 'variable_name', 'filter_type', 'is_required', 'order_position']);
        });
    }
};
