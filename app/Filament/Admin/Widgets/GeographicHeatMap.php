<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Event;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GeographicHeatMap extends Widget
{
    protected static string $view = 'filament.admin.widgets.geographic-heat-map';
    
    protected static ?int $sort = 7;
    
    protected static bool $isLazy = true; // Lazy load
    
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
        'xl' => 1,
    ];
    
    protected static ?string $pollingInterval = null; // Disable polling
    
    public function getCountryData(): array
    {
        return Cache::remember('admin.country_heat_map', 1800, function () {
            // Simplified query without country column
            $data = DB::table('events')
                ->join('bookings', 'events.id', '=', 'bookings.event_id')
                ->select(
                    'events.city',
                    DB::raw('COUNT(DISTINCT bookings.id) as booking_count'),
                    DB::raw('SUM(bookings.total_amount) as total_revenue'),
                    DB::raw('COUNT(DISTINCT events.id) as event_count')
                )
                ->where('bookings.status', 'confirmed')
                ->where('bookings.created_at', '>=', now()->subDays(30))
                ->groupBy('events.city')
                ->limit(20) // Limit for performance
                ->get();
            
            // Group by city for now
            $cityData = [];
            foreach ($data as $row) {
                $city = $row->city ?? 'Unknown';
                $cityData[$city] = [
                    'name' => $city,
                    'bookings' => $row->booking_count,
                    'revenue' => $row->total_revenue,
                    'events' => $row->event_count,
                    'revenue_formatted' => 'KES ' . number_format($row->total_revenue, 0),
                ];
            }
            
            // Calculate intensity
            $maxBookings = max(array_column($cityData, 'bookings')) ?: 1;
            
            foreach ($cityData as &$city) {
                $city['intensity'] = round(($city['bookings'] / $maxBookings) * 100);
            }
            
            // Sort by bookings
            uasort($cityData, function ($a, $b) {
                return $b['bookings'] <=> $a['bookings'];
            });
            
            return $cityData;
        });
    }
    
    public function getTopCountries(): array
    {
        // Return top cities since we don't have country data
        $allCities = $this->getCountryData();
        return array_slice($allCities, 0, 5, true);
    }
    
    protected function getCountryName($code): string
    {
        // Return the city name as is
        return $code;
    }
}