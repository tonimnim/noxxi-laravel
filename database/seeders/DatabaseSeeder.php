<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed admin users first
        $this->call(AdminSeeder::class);
        
        // Seed event categories
        $this->call(EventCategorySeeder::class);
        
        // Seed African cities
        $this->call(AfricanCitiesSeeder::class);
        
        // Create test user
        User::factory()->create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Create test organizer
        $organizer = User::factory()->create([
            'full_name' => 'Test Organizer',
            'email' => 'organizer@example.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        // Create organizer profile
        \App\Models\Organizer::create([
            'id' => \Str::uuid(),
            'user_id' => $organizer->id,
            'business_name' => 'Test Events Company',
            'business_country' => 'KE',
            'business_timezone' => 'Africa/Nairobi',
            'default_currency' => 'KES',
            'commission_rate' => 10.00,
            'settlement_period_days' => 7,
            'is_active' => true,
        ]);
    }
}
