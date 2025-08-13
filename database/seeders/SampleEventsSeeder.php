<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SampleEventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first organizer or create one
        $organizer = Organizer::first();
        
        if (!$organizer) {
            return; // Skip if no organizer exists
        }

        // Get categories
        $concerts = EventCategory::where('slug', 'concerts')->first();
        $football = EventCategory::where('slug', 'football')->first();
        $cinema = EventCategory::where('slug', 'cinema')->first();
        $wellness = EventCategory::where('slug', 'wellness')->first();
        $conferences = EventCategory::where('slug', 'conferences-workshops')->first();
        $festivals = EventCategory::where('slug', 'festivals')->first();
        
        $events = [
            [
                'title' => 'Afrobeats Live Concert',
                'description' => 'An incredible night of Afrobeats music featuring top African artists',
                'category_id' => $concerts?->id ?? EventCategory::where('slug', 'events')->first()->id,
                'venue_name' => 'Lagos Continental Hotel',
                'venue_address' => '52A Kofo Abayomi Street, Victoria Island',
                'city' => 'Lagos',
                'event_date' => Carbon::parse('2025-09-15 20:00'),
                'end_date' => Carbon::parse('2025-09-15 23:30'),
                'capacity' => 1500,
                'tickets_sold' => 892,
                'min_price' => 15000,
                'max_price' => 50000,
                'currency' => 'NGN',
                'status' => 'published',
            ],
            [
                'title' => 'Kenya Premier League: Gor Mahia vs AFC Leopards',
                'description' => 'The Mashemeji Derby - biggest football rivalry in Kenya',
                'category_id' => $football?->id ?? EventCategory::where('slug', 'sports')->first()->id,
                'venue_name' => 'Nyayo National Stadium',
                'venue_address' => 'Langata Road',
                'city' => 'Nairobi',
                'event_date' => Carbon::parse('2025-08-25 15:00'),
                'capacity' => 30000,
                'tickets_sold' => 24500,
                'min_price' => 200,
                'max_price' => 5000,
                'currency' => 'KES',
                'status' => 'published',
            ],
            [
                'title' => 'Black Panther: Wakanda Forever - Special Screening',
                'description' => 'Exclusive cinema screening with Q&A session',
                'category_id' => $cinema?->id ?? EventCategory::where('slug', 'cinema')->first()->id,
                'venue_name' => 'Century Cinemax',
                'venue_address' => 'Junction Mall, Ngong Road',
                'city' => 'Nairobi',
                'event_date' => Carbon::parse('2025-08-22 19:30'),
                'capacity' => 350,
                'tickets_sold' => 280,
                'min_price' => 500,
                'max_price' => 1500,
                'currency' => 'KES',
                'status' => 'published',
            ],
            [
                'title' => 'Wellness & Mindfulness Retreat',
                'description' => 'A full day of yoga, meditation, and wellness activities',
                'category_id' => $wellness?->id ?? EventCategory::where('slug', 'experiences')->first()->id,
                'venue_name' => 'Karura Forest',
                'venue_address' => 'Limuru Road',
                'city' => 'Nairobi',
                'event_date' => Carbon::parse('2025-09-05 08:00'),
                'end_date' => Carbon::parse('2025-09-05 17:00'),
                'capacity' => 100,
                'tickets_sold' => 45,
                'min_price' => 3500,
                'max_price' => 7500,
                'currency' => 'KES',
                'status' => 'published',
            ],
            [
                'title' => 'Africa Tech Summit 2025',
                'description' => 'Premier technology conference for African innovators',
                'category_id' => $conferences?->id ?? EventCategory::where('slug', 'events')->first()->id,
                'venue_name' => 'Kigali Convention Centre',
                'venue_address' => 'KG 2 Roundabout',
                'city' => 'Kigali',
                'event_date' => Carbon::parse('2025-10-10 09:00'),
                'end_date' => Carbon::parse('2025-10-12 18:00'),
                'capacity' => 2000,
                'tickets_sold' => 1250,
                'min_price' => 50,
                'max_price' => 500,
                'currency' => 'USD',
                'status' => 'published',
            ],
            [
                'title' => 'Blankets and Wine Festival',
                'description' => 'Outdoor music and lifestyle festival',
                'category_id' => $festivals?->id ?? EventCategory::where('slug', 'events')->first()->id,
                'venue_name' => 'Lugogo Cricket Oval',
                'venue_address' => 'Lugogo Bypass',
                'city' => 'Kampala',
                'event_date' => Carbon::parse('2025-08-31 12:00'),
                'end_date' => Carbon::parse('2025-08-31 22:00'),
                'capacity' => 5000,
                'tickets_sold' => 3200,
                'min_price' => 100000,
                'max_price' => 250000,
                'currency' => 'UGX',
                'status' => 'published',
            ],
        ];

        foreach ($events as $eventData) {
            $eventData['organizer_id'] = $organizer->id;
            $eventData['slug'] = \Str::slug($eventData['title']);
            
            // Check if event already exists
            $existingEvent = Event::where('slug', $eventData['slug'])->first();
            if (!$existingEvent) {
                Event::create($eventData);
            }
        }
    }
}