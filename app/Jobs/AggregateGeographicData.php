<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AggregateGeographicData implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Single optimized query with CTEs for country statistics
            $countryData = \DB::select("
                WITH country_stats AS (
                    SELECT 
                        COALESCE(u.country, o.country) as country,
                        COUNT(DISTINCT u.id) as user_count,
                        COUNT(DISTINCT o.id) as organizer_count,
                        COUNT(DISTINCT e.id) as listing_count,
                        COUNT(DISTINCT b.id) as booking_count,
                        COALESCE(SUM(b.total_amount), 0) as total_revenue
                    FROM users u
                    LEFT JOIN organizers o ON o.user_id = u.id
                    LEFT JOIN events e ON e.organizer_id = o.id AND e.status = 'published'
                    LEFT JOIN bookings b ON b.event_id = e.id AND b.status = 'confirmed'
                    WHERE (u.country IS NOT NULL OR o.country IS NOT NULL)
                    AND u.created_at >= NOW() - INTERVAL '30 days'
                    GROUP BY COALESCE(u.country, o.country)
                )
                SELECT 
                    country,
                    user_count,
                    organizer_count,
                    listing_count,
                    booking_count,
                    total_revenue,
                    -- Calculate activity score for heat map coloring
                    (
                        (user_count * 1.0) +
                        (organizer_count * 5.0) +
                        (listing_count * 3.0) +
                        (booking_count * 2.0) +
                        (total_revenue / 10000.0)
                    ) as activity_score
                FROM country_stats
                ORDER BY activity_score DESC
            ");

            // Transform data for easier access
            $processedData = [];
            $maxScore = 0;
            
            foreach ($countryData as $data) {
                $processedData[$data->country] = [
                    'users' => $data->user_count,
                    'organizers' => $data->organizer_count,
                    'listings' => $data->listing_count,
                    'bookings' => $data->booking_count,
                    'revenue' => $data->total_revenue,
                    'score' => $data->activity_score,
                ];
                $maxScore = max($maxScore, $data->activity_score);
            }

            // Add normalized scores for heat map coloring
            foreach ($processedData as $country => &$stats) {
                $stats['normalized_score'] = $maxScore > 0 ? ($stats['score'] / $maxScore) : 0;
                $stats['heat_level'] = $this->getHeatLevel($stats['normalized_score']);
            }

            // Get top countries for display
            $topCountries = array_slice($processedData, 0, 10, true);

            // Prepare final data structure
            $aggregatedData = [
                'countries' => $processedData,
                'top_countries' => $topCountries,
                'summary' => [
                    'total_countries' => count($processedData),
                    'total_users' => array_sum(array_column($processedData, 'users')),
                    'total_revenue' => array_sum(array_column($processedData, 'revenue')),
                    'most_active' => array_key_first($processedData),
                ],
                'last_updated' => now(),
            ];

            // Store in cache for 30 minutes
            \Cache::put('admin.geographic.data', $aggregatedData, 1800);
            
            // Log successful aggregation
            \App\Services\ActivityService::logSystem(
                'info',
                'Geographic data aggregation completed',
                [
                    'countries_processed' => count($processedData),
                    'execution_time' => round(microtime(true) - LARAVEL_START, 2) . 's',
                ]
            );
            
        } catch (\Exception $e) {
            \Log::error('Geographic data aggregation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Store empty data to prevent widget errors
            \Cache::put('admin.geographic.data', [
                'countries' => [],
                'top_countries' => [],
                'summary' => [
                    'total_countries' => 0,
                    'total_users' => 0,
                    'total_revenue' => 0,
                    'most_active' => null,
                ],
                'last_updated' => now(),
            ], 300); // Cache for 5 minutes on error
        }
    }

    /**
     * Determine heat level based on normalized score
     */
    private function getHeatLevel(float $score): string
    {
        if ($score >= 0.8) return 'very-high';
        if ($score >= 0.6) return 'high';
        if ($score >= 0.4) return 'medium';
        if ($score >= 0.2) return 'low';
        return 'very-low';
    }
}
