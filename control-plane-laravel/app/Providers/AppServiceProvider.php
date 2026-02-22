<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate Limiter for OTP resend
        RateLimiter::for('otp-resend', function (Request $request) {
            return Limit::perMinute(1)->by($request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Please wait 60 seconds before requesting a new code.'
                ], 429, $headers);
            });
        });

        // Ensure a default admin user exists if the database is empty.
        // We only do this for web requests to avoid interfering with CLI/build processes.
        if (!app()->runningInConsole() && !app()->runningUnitTests()) {
            $this->ensureAdminUser();
        }
    }

    /**
     * Check for users and seed the default admin if missing.
     */
    private function ensureAdminUser(): void
    {
        try {
            // Check if essential tables exist and users table is empty
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && 
                \Illuminate\Support\Facades\Schema::hasTable('roles')) {
                
                if (\App\Models\User::count() === 0) {
                    (new \Database\Seeders\AdminSeeder())->run();
                }
            }
        } catch (\Exception $e) {
            // Silence errors during early setup phase (e.g. database not yet reachable)
            \Illuminate\Support\Facades\Log::debug('Admin user auto-creation skipped: ' . $e->getMessage());
        }
    }
}
