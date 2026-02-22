<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->bigInteger('file_size')->nullable()->after('output_path');
            $table->json('delivery_log_json')->nullable()->after('error_log');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->boolean('require_otp')->default(false);
            $table->string('otp_code')->nullable(); // Though usually generated per execution
        });
    }

    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->dropColumn(['file_size', 'delivery_log_json']);
        });

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['require_otp', 'otp_code']);
        });
    }
};
