<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * GUARANTEES: Creates default admin user if and only if:
     * - users table exists (migrations have run)
     * - users table is empty (fresh database)
     * 
     * IDEMPOTENT: Safe to run multiple times, will never duplicate or modify existing users.
     */
    public function run(): void
    {
        // Guard 1: Ensure users table exists (migrations completed)
        if (!Schema::hasTable('users')) {
            $this->command->warn('Users table does not exist yet. Skipping admin creation.');
            return;
        }

        // Guard 2: Only create if database is empty
        if (User::count() > 0) {
            $this->command->info('Users already exist. Skipping admin creation.');
            return;
        }

        // Ensure the Admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        
        // Create the default admin user
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@system.local',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
        
        $this->command->info('✓ Default admin user created: admin@system.local / admin123');
    }
}
