<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);
        
        Role::firstOrCreate(['name' => 'Designer']);
        Role::firstOrCreate(['name' => 'Consumer']);

        $this->call(DemoSeeder::class);
    }
}
