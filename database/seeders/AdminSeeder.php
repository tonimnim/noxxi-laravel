<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@noxxi.com'],
            [
                'id' => Str::uuid(),
                'full_name' => 'System Administrator',
                'email' => 'admin@noxxi.com',
                'password' => Hash::make('admin@2024'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_number' => '+254700000000',
            ]
        );

        // Create secondary admin for testing
        $secondaryAdmin = User::firstOrCreate(
            ['email' => 'admin2@noxxi.com'],
            [
                'id' => Str::uuid(),
                'full_name' => 'Secondary Admin',
                'email' => 'admin2@noxxi.com',
                'password' => Hash::make('admin@2024'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'phone_number' => '+254700000001',
            ]
        );

        $this->command->info('Admin users seeded successfully:');
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['admin@noxxi.com', 'admin@2024', 'Super Admin'],
                ['admin2@noxxi.com', 'admin@2024', 'Secondary Admin'],
            ]
        );
    }
}