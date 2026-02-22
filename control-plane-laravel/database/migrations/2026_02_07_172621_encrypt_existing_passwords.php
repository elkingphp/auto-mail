<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\EmailServer;
use App\Models\FtpServer;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Encrypt EmailServer passwords if not already encrypted
        EmailServer::all()->each(function ($server) {
            try {
                // Try to decrypt to check if it's already encrypted
                Crypt::decryptString($server->password);
            } catch (\Exception $e) {
                // If decryption fails, it's likely plain text, so encrypt it
                $server->password = $server->password; // Eloquent cast will handle encryption on save
                $server->save();
            }
        });

        // Encrypt FtpServer passwords if not already encrypted
        FtpServer::all()->each(function ($server) {
            try {
                Crypt::decryptString($server->password);
            } catch (\Exception $e) {
                $server->password = $server->password;
                $server->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to decrypt without knowing if it was originally plain or not,
        // but typically encryption migrations are one-way for security.
    }
};
