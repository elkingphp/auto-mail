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
        // 1. Email Servers
        Schema::create('email_servers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('driver')->default('smtp'); // smtp, sendmail, log
            $table->string('host');
            $table->integer('port')->default(587);
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // Encrypted at rest
            $table->string('encryption')->nullable()->default('tls'); // tls, ssl, null
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. FTP Servers
        Schema::create('ftp_servers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('host');
            $table->integer('port')->default(21);
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // Encrypted at rest
            $table->string('root_path')->nullable()->default('/');
            $table->boolean('passive_mode')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Email Templates
        Schema::create('email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('subject');
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->json('variables')->nullable(); // [{"key": "report_name", "desc": "Report Name"}]
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Update Schedules Table
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignUuid('email_server_id')->nullable()->constrained('email_servers')->nullOnDelete();
            $table->foreignUuid('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            // delivery_mode: 'email', 'ftp', 'both', 'none'
            // We can infer this from relationships or add an explicit flag later. 
            // For now, presence of relationships implies intent, but an explicit flag is safer for logic.
            $table->string('delivery_mode')->default('email')->after('is_active'); 
        });

        // 5. Schedule <-> FTP Server Pivot
        Schema::create('schedule_ftp_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignUuid('ftp_server_id')->constrained('ftp_servers')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_ftp_servers');
        
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['email_server_id']);
            $table->dropForeign(['email_template_id']);
            $table->dropColumn(['email_server_id', 'email_template_id', 'delivery_mode']);
        });

        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('ftp_servers');
        Schema::dropIfExists('email_servers');
    }
};
