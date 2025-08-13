<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organizer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@noxxi.com'],
            [
                'id' => Str::uuid(),
                'full_name' => 'Admin User',
                'phone_number' => '+254700000001',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create organizer user
        $organizerUser = User::firstOrCreate(
            ['email' => 'organizer@noxxi.com'],
            [
                'id' => Str::uuid(),
                'full_name' => 'John Organizer',
                'phone_number' => '+254700000002',
                'password' => Hash::make('password123'),
                'role' => 'organizer',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create organizer profile if doesn't exist
        if ($organizerUser->wasRecentlyCreated || !$organizerUser->organizer) {
            Organizer::create([
                'id' => Str::uuid(),
                'user_id' => $organizerUser->id,
                'business_name' => 'Awesome Events Ltd',
                'business_email' => 'contact@awesomeevents.com',
                'business_country' => 'KE',
                'business_timezone' => 'Africa/Nairobi',
                'default_currency' => 'KES',
                'commission_rate' => 10.00,
                'settlement_period_days' => 7,
                'is_active' => true,
                'approved_at' => now(),
            ]);
        }

        // Create regular user
        $user = User::firstOrCreate(
            ['email' => 'user@noxxi.com'],
            [
                'id' => Str::uuid(),
                'full_name' => 'Jane User',
                'phone_number' => '+254700000003',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Test users created:');
        $this->command->info('Admin: admin@noxxi.com / password123');
        $this->command->info('Organizer: organizer@noxxi.com / password123');
        $this->command->info('User: user@noxxi.com / password123');
    }
}