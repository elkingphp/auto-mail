<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->text('notification_emails')->nullable()->after('notification_email');
        });
        
        // Migrate data if any exists (unlikely in this dev phase, but good practice)
        DB::table('executions')->whereNotNull('notification_email')->get()->each(function ($exec) {
            DB::table('executions')->where('id', $exec->id)->update([
                'notification_emails' => json_encode([$exec->notification_email])
            ]);
        });

        Schema::table('executions', function (Blueprint $table) {
            $table->dropColumn('notification_email');
        });
    }

    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table) {
            $table->string('notification_email')->nullable()->after('triggered_by');
        });

        Schema::table('executions', function (Blueprint $table) {
            $table->dropColumn('notification_emails');
        });
    }
};
