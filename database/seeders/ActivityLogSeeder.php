<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Organizer;
use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing models for relationships
        $user = User::first();
        $organizer = Organizer::first();
        $event = Event::first();

        // Create sample activities
        $activities = [
            // Payment activities (critical/important)
            [
                'type' => ActivityLog::TYPE_PAYMENT,
                'action' => ActivityLog::ACTION_COMPLETED,
                'level' => ActivityLog::LEVEL_IMPORTANT,
                'title' => 'Large payment processed',
                'description' => 'Payment of KES 250,000 processed for Jazz Night event',
                'properties' => ['amount' => 250000, 'currency' => 'KES', 'event' => 'Jazz Night'],
                'created_at' => now()->subMinutes(5),
            ],
            [
                'type' => ActivityLog::TYPE_PAYMENT,
                'action' => ActivityLog::ACTION_FAILED,
                'level' => ActivityLog::LEVEL_CRITICAL,
                'title' => 'Payment failed',
                'description' => 'M-Pesa payment failed for booking #B12345',
                'properties' => ['amount' => 5000, 'method' => 'mpesa', 'error' => 'Insufficient funds'],
                'created_at' => now()->subMinutes(15),
            ],
            
            // Organizer activities
            [
                'type' => ActivityLog::TYPE_ORGANIZER,
                'action' => ActivityLog::ACTION_REGISTERED,
                'level' => ActivityLog::LEVEL_IMPORTANT,
                'title' => 'New organizer registered',
                'description' => 'Safari Events Ltd joined the platform',
                'subject_type' => $organizer ? get_class($organizer) : null,
                'subject_id' => $organizer?->id,
                'properties' => ['business_name' => 'Safari Events Ltd', 'country' => 'KE'],
                'created_at' => now()->subMinutes(30),
            ],
            [
                'type' => ActivityLog::TYPE_ORGANIZER,
                'action' => ActivityLog::ACTION_APPROVED,
                'level' => ActivityLog::LEVEL_IMPORTANT,
                'title' => 'Organizer verified',
                'description' => 'Nairobi Concerts verified and approved',
                'properties' => ['business_name' => 'Nairobi Concerts', 'verification_level' => 'gold'],
                'created_at' => now()->subHours(1),
            ],
            
            // Event activities
            [
                'type' => ActivityLog::TYPE_EVENT,
                'action' => ActivityLog::ACTION_CREATED,
                'level' => ActivityLog::LEVEL_INFO,
                'title' => 'New event created',
                'description' => 'Tech Summit 2025 has been created',
                'subject_type' => $event ? get_class($event) : null,
                'subject_id' => $event?->id,
                'properties' => ['event_title' => 'Tech Summit 2025', 'category' => 'Conference'],
                'created_at' => now()->subHours(2),
            ],
            [
                'type' => ActivityLog::TYPE_EVENT,
                'action' => ActivityLog::ACTION_UPDATED,
                'level' => ActivityLog::LEVEL_INFO,
                'title' => 'Event updated',
                'description' => 'Comedy Night ticket prices adjusted',
                'properties' => ['event_title' => 'Comedy Night', 'change' => 'Price reduced by 20%'],
                'created_at' => now()->subHours(3),
            ],
            
            // User activities
            [
                'type' => ActivityLog::TYPE_USER,
                'action' => ActivityLog::ACTION_REGISTERED,
                'level' => ActivityLog::LEVEL_INFO,
                'title' => 'New user signup',
                'description' => '50 new users registered today',
                'causer_type' => $user ? get_class($user) : null,
                'causer_id' => $user?->id,
                'properties' => ['count' => 50, 'source' => 'mobile_app'],
                'created_at' => now()->subHours(4),
            ],
            
            // System activities
            [
                'type' => ActivityLog::TYPE_SYSTEM,
                'action' => 'maintenance',
                'level' => ActivityLog::LEVEL_IMPORTANT,
                'title' => 'System maintenance completed',
                'description' => 'Database optimization completed successfully',
                'properties' => ['duration' => '15 minutes', 'tables_optimized' => 12],
                'created_at' => now()->subHours(6),
            ],
            [
                'type' => ActivityLog::TYPE_SYSTEM,
                'action' => 'alert',
                'level' => ActivityLog::LEVEL_CRITICAL,
                'title' => 'High server load detected',
                'description' => 'API response time exceeded 2 seconds',
                'properties' => ['avg_response_time' => 2.5, 'peak_requests' => 1500],
                'created_at' => now()->subHours(8),
            ],
            
            // More recent activities
            [
                'type' => ActivityLog::TYPE_PAYMENT,
                'action' => ActivityLog::ACTION_COMPLETED,
                'level' => ActivityLog::LEVEL_INFO,
                'title' => 'Bulk ticket purchase',
                'description' => '25 tickets sold for Football Match',
                'properties' => ['quantity' => 25, 'total' => 50000, 'currency' => 'KES'],
                'created_at' => now()->subMinutes(2),
            ],
            [
                'type' => ActivityLog::TYPE_USER,
                'action' => ActivityLog::ACTION_LOGIN,
                'level' => ActivityLog::LEVEL_INFO,
                'title' => 'Admin login',
                'description' => 'Admin user logged in from new device',
                'properties' => ['device' => 'Chrome/Windows', 'ip' => '197.156.x.x'],
                'created_at' => now()->subMinutes(1),
            ],
        ];

        foreach ($activities as $activity) {
            ActivityLog::create(array_merge(
                ['id' => Str::uuid()],
                $activity,
                [
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder/1.0',
                ]
            ));
        }

        $this->command->info('Sample activity logs created successfully!');
    }
}
