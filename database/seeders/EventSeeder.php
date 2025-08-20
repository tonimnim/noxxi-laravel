<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get organizers
        $organizers = Organizer::all();
        if ($organizers->count() < 4) {
            $this->command->error('Not enough organizers in database. Need at least 4.');
            return;
        }

        // Get categories
        $concertCategory = EventCategory::where('slug', 'concerts')->first();
        $footballCategory = EventCategory::where('slug', 'football')->first();
        $cinemaCategory = EventCategory::where('slug', 'cinema')->first();
        $adventureCategory = EventCategory::where('slug', 'adventure')->first();
        $festivalsCategory = EventCategory::where('slug', 'festivals')->first();

        $events = [
            // Event 1: Concert in Nairobi (Organizer 1)
            [
                'id' => Str::uuid(),
                'organizer_id' => $organizers[0]->id,
                'category_id' => $concertCategory->id ?? EventCategory::first()->id,
                'title' => 'Afrobeat Night Live with Burna Boy',
                'slug' => 'afrobeat-night-live-burna-boy',
                'description' => 'Experience an unforgettable night of African music as Grammy winner Burna Boy brings his electrifying performance to Nairobi. Join thousands of fans for a celebration of African excellence, featuring special guest performances from Kenya\'s top artists.',
                'venue_name' => 'Carnivore Grounds',
                'venue_address' => 'Langata Road, Nairobi',
                'city' => 'Nairobi',
                'latitude' => -1.3230,
                'longitude' => 36.7850,
                'event_date' => now()->addDays(15)->setTime(19, 0),
                'end_date' => now()->addDays(15)->setTime(23, 30),
                'capacity' => 5000,
                'tickets_sold' => 3750,
                'min_price' => 3500,
                'max_price' => 25000,
                'currency' => 'KES',
                'status' => 'published',
                'featured' => true,
                'featured_until' => now()->addDays(10),
                'view_count' => 15420,
                'share_count' => 892,
                'age_restriction' => 18,
                'ticket_types' => [
                    ['name' => 'Regular', 'price' => 3500, 'quantity' => 3000, 'description' => 'General admission'],
                    ['name' => 'VIP', 'price' => 8500, 'quantity' => 1500, 'description' => 'VIP area access with complimentary drinks'],
                    ['name' => 'VVIP', 'price' => 25000, 'quantity' => 500, 'description' => 'Premium experience with meet & greet']
                ],
                'tags' => ['afrobeat', 'burna boy', 'live music', 'concert', 'nairobi'],
                'cover_image_url' => 'https://img.freepik.com/free-psd/saturday-party-social-media-template_23-2150899884.jpg',
                'images' => [
                    'https://img.freepik.com/free-psd/saturday-party-social-media-template_23-2150899884.jpg',
                    'https://img.freepik.com/free-photo/excited-audience-watching-confetti-fireworks-having-fun-music-festival-night-copy-space_637285-559.jpg'
                ],
                'terms_conditions' => 'No refunds. Age 18+. Valid ID required. No professional cameras allowed.',
                'refund_policy' => 'Non-refundable unless event is cancelled by organizer.',
            ],

            // Event 2: Football Match in Lagos (Organizer 2)
            [
                'id' => Str::uuid(),
                'organizer_id' => $organizers[1]->id,
                'category_id' => $footballCategory->id ?? EventCategory::first()->id,
                'title' => 'Nigeria Super Eagles vs Ghana Black Stars - AFCON Qualifier',
                'slug' => 'nigeria-vs-ghana-afcon-qualifier',
                'description' => 'The ultimate West African football rivalry continues! Watch as the Super Eagles take on the Black Stars in this crucial AFCON qualifier match. Experience the passion, pride, and electric atmosphere at the National Stadium.',
                'venue_name' => 'National Stadium',
                'venue_address' => 'Surulere, Lagos',
                'city' => 'Lagos',
                'latitude' => 6.4969,
                'longitude' => 3.3650,
                'event_date' => now()->addDays(8)->setTime(16, 0),
                'end_date' => now()->addDays(8)->setTime(18, 0),
                'capacity' => 45000,
                'tickets_sold' => 38500,
                'min_price' => 2000,
                'max_price' => 50000,
                'currency' => 'NGN',
                'status' => 'published',
                'featured' => true,
                'featured_until' => now()->addDays(7),
                'view_count' => 89320,
                'share_count' => 4521,
                'age_restriction' => 0,
                'ticket_types' => [
                    ['name' => 'Popular Stand', 'price' => 2000, 'quantity' => 30000, 'description' => 'General seating'],
                    ['name' => 'Covered Stand', 'price' => 5000, 'quantity' => 10000, 'description' => 'Covered seating area'],
                    ['name' => 'VIP Box', 'price' => 50000, 'quantity' => 5000, 'description' => 'Premium seats with hospitality']
                ],
                'tags' => ['football', 'super eagles', 'ghana', 'afcon', 'sports', 'lagos'],
                'cover_image_url' => 'https://img.freepik.com/free-photo/soccer-stadium-with-fans-crowd_1150-14434.jpg',
                'images' => [
                    'https://img.freepik.com/free-photo/soccer-stadium-with-fans-crowd_1150-14434.jpg',
                    'https://img.freepik.com/free-photo/football-soccer-ball-kickoff-game-sunset_1150-14650.jpg'
                ],
                'terms_conditions' => 'Stadium rules apply. No weapons or dangerous items. Subject to security checks.',
                'refund_policy' => 'Refunds only if match is cancelled or postponed.',
            ],

            // Event 3: Cinema - Black Panther 3 Premier in Johannesburg (Organizer 3)
            [
                'id' => Str::uuid(),
                'organizer_id' => $organizers[2]->id,
                'category_id' => $cinemaCategory->id ?? EventCategory::first()->id,
                'title' => 'Black Panther 3: Wakanda Rising - Exclusive African Premiere',
                'slug' => 'black-panther-3-african-premiere',
                'description' => 'Be among the first in the world to watch the highly anticipated Black Panther 3! Join us for the exclusive African premiere featuring red carpet arrivals, African fashion showcase, and special appearances by cast members.',
                'venue_name' => 'Ster-Kinekor IMAX Sandton',
                'venue_address' => 'Sandton City, Rivonia Road',
                'city' => 'Johannesburg',
                'latitude' => -26.1076,
                'longitude' => 28.0567,
                'event_date' => now()->addDays(20)->setTime(19, 30),
                'end_date' => now()->addDays(20)->setTime(22, 30),
                'capacity' => 400,
                'tickets_sold' => 385,
                'min_price' => 350,
                'max_price' => 2500,
                'currency' => 'ZAR',
                'status' => 'published',
                'featured' => false,
                'view_count' => 28940,
                'share_count' => 2103,
                'age_restriction' => 13,
                'ticket_types' => [
                    ['name' => 'Standard', 'price' => 350, 'quantity' => 200, 'description' => 'Regular IMAX seat'],
                    ['name' => 'Premium', 'price' => 850, 'quantity' => 150, 'description' => 'Premium recliner seats'],
                    ['name' => 'VIP Package', 'price' => 2500, 'quantity' => 50, 'description' => 'Red carpet access, premium seat, gift bag']
                ],
                'tags' => ['cinema', 'black panther', 'marvel', 'premiere', 'imax', 'johannesburg'],
                'cover_image_url' => 'https://img.freepik.com/free-photo/view-3d-cinema-theatre-room_23-2151067359.jpg',
                'images' => [
                    'https://img.freepik.com/free-photo/view-3d-cinema-theatre-room_23-2151067359.jpg',
                    'https://img.freepik.com/free-photo/movie-theater-with-red-seats_1340-22996.jpg'
                ],
                'terms_conditions' => 'Age restriction PG-13. No recording devices. Formal dress code for VIP tickets.',
                'refund_policy' => 'No refunds or exchanges.',
            ],

            // Event 4: Safari Adventure in Masai Mara (Organizer 4)
            [
                'id' => Str::uuid(),
                'organizer_id' => $organizers[3]->id,
                'category_id' => $adventureCategory->id ?? EventCategory::first()->id,
                'title' => '3-Day Masai Mara Safari & Hot Air Balloon Experience',
                'slug' => 'masai-mara-safari-balloon-experience',
                'description' => 'Embark on an unforgettable 3-day safari adventure in the world-famous Masai Mara. Witness the Great Migration, enjoy game drives with experienced guides, and float above the savannah in a hot air balloon at sunrise. Includes luxury tented camp accommodation and all meals.',
                'venue_name' => 'Masai Mara National Reserve',
                'venue_address' => 'Narok County',
                'city' => 'Narok',
                'latitude' => -1.4061,
                'longitude' => 35.1426,
                'event_date' => now()->addDays(30)->setTime(6, 0),
                'end_date' => now()->addDays(33)->setTime(18, 0),
                'capacity' => 20,
                'tickets_sold' => 14,
                'min_price' => 85000,
                'max_price' => 150000,
                'currency' => 'KES',
                'status' => 'published',
                'featured' => true,
                'featured_until' => now()->addDays(25),
                'view_count' => 5230,
                'share_count' => 412,
                'age_restriction' => 5,
                'ticket_types' => [
                    ['name' => 'Standard Package', 'price' => 85000, 'quantity' => 12, 'description' => 'Shared tent, all meals, 2 game drives daily'],
                    ['name' => 'Luxury Package', 'price' => 150000, 'quantity' => 8, 'description' => 'Private luxury tent, premium meals, private guide, hot air balloon']
                ],
                'tags' => ['safari', 'masai mara', 'wildlife', 'adventure', 'hot air balloon', 'kenya'],
                'cover_image_url' => 'https://img.freepik.com/free-photo/beautiful-shot-african-elephants-field_181624-38895.jpg',
                'images' => [
                    'https://img.freepik.com/free-photo/beautiful-shot-african-elephants-field_181624-38895.jpg',
                    'https://img.freepik.com/free-photo/hot-air-balloon-floating-sunset-valley-cappadocia-turkey_1150-11030.jpg',
                    'https://img.freepik.com/free-photo/camping-tent-by-lake-sunrise_1150-10687.jpg'
                ],
                'terms_conditions' => 'Minimum age 5 years. Travel insurance required. Subject to weather conditions.',
                'refund_policy' => '50% refund if cancelled 30 days before. No refund within 30 days.',
            ],

            // Event 5: Music Festival in Cape Town (Organizer 1 - gets 2nd event)
            [
                'id' => Str::uuid(),
                'organizer_id' => $organizers[0]->id, // Same as first organizer
                'category_id' => $festivalsCategory->id ?? EventCategory::first()->id,
                'title' => 'Cape Town International Jazz Festival 2025',
                'slug' => 'cape-town-jazz-festival-2025',
                'description' => 'Africa\'s Grandest Gathering returns! Experience 2 days of world-class jazz, soul, funk, and Afrobeat across 5 stages. Featuring over 40 international and local artists, food villages, craft markets, and the ultimate celebration of African music culture.',
                'venue_name' => 'Cape Town International Convention Centre',
                'venue_address' => '1 Lower Long Street',
                'city' => 'Cape Town',
                'latitude' => -33.9156,
                'longitude' => 18.4262,
                'event_date' => now()->addDays(45)->setTime(12, 0),
                'end_date' => now()->addDays(47)->setTime(23, 0),
                'capacity' => 35000,
                'tickets_sold' => 22100,
                'min_price' => 950,
                'max_price' => 4500,
                'currency' => 'ZAR',
                'status' => 'published',
                'featured' => true,
                'featured_until' => now()->addDays(40),
                'view_count' => 42150,
                'share_count' => 3204,
                'age_restriction' => 0,
                'ticket_types' => [
                    ['name' => 'Day Pass', 'price' => 950, 'quantity' => 20000, 'description' => 'Single day access'],
                    ['name' => 'Weekend Pass', 'price' => 1750, 'quantity' => 10000, 'description' => 'Full festival access'],
                    ['name' => 'VIP Weekend', 'price' => 4500, 'quantity' => 5000, 'description' => 'VIP areas, premium viewing, complimentary food & drinks']
                ],
                'tags' => ['jazz', 'festival', 'music', 'cape town', 'international', 'african music'],
                'cover_image_url' => 'https://img.freepik.com/free-psd/saturday-party-social-media-template_23-2150899884.jpg',
                'images' => [
                    'https://img.freepik.com/free-psd/saturday-party-social-media-template_23-2150899884.jpg',
                    'https://img.freepik.com/free-photo/dj-playing-music-festival_1150-13694.jpg',
                    'https://img.freepik.com/free-photo/excited-audience-watching-confetti-fireworks-having-fun-music-festival-night-copy-space_637285-559.jpg'
                ],
                'terms_conditions' => 'Festival wristbands must be worn at all times. No re-entry without wristband. No professional recording equipment.',
                'refund_policy' => 'Refunds available up to 14 days before event. 20% admin fee applies.',
            ],
        ];

        foreach ($events as $eventData) {
            // Set some additional fields
            $eventData['qr_secret_key'] = Str::random(32);
            $eventData['published_at'] = now()->subDays(rand(1, 10));
            $eventData['first_published_at'] = $eventData['published_at'];
            $eventData['created_at'] = now();
            $eventData['updated_at'] = now();

            Event::create($eventData);
            $this->command->info("Created event: {$eventData['title']}");
        }

        $this->command->info('Successfully seeded 5 diverse events!');
    }
}