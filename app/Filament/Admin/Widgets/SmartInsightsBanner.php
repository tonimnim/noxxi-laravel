<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SmartInsightsBanner extends Widget
{
    protected static string $view = 'filament.admin.widgets.smart-insights-banner';
    
    protected static ?int $sort = 0; // Show at the very top
    
    protected static bool $isLazy = false; // Load immediately as it's important
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = null; // Disable polling
    
    public function getInsights(): array
    {
        return Cache::remember('admin.smart_insights', 300, function () {
            $insights = [];
            
            // Check for pending payouts older than 48 hours
            $pendingPayouts = DB::table('payouts')
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subHours(48))
                ->count();
            
            if ($pendingPayouts > 0) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'message' => "{$pendingPayouts} payouts have been pending for >48 hours",
                    'action' => [
                        'label' => 'Process Payouts',
                        'url' => route('filament.admin.resources.payouts.index'),
                    ],
                ];
            }
            
            // Check for organizers with bookings ready for payout (simplified approach)
            $readyForPayout = DB::table('bookings')
                ->select(
                    DB::raw('COUNT(DISTINCT events.organizer_id) as organizer_count'),
                    DB::raw('SUM(bookings.total_amount) as total')
                )
                ->join('events', 'bookings.event_id', '=', 'events.id')
                ->where('bookings.payment_status', 'paid')
                ->where('bookings.created_at', '<', now()->subDays(7))
                ->first();
            
            // Check how many of these have pending/unpaid payouts
            $lastPayoutDate = DB::table('payouts')
                ->whereIn('status', ['paid', 'processing', 'approved'])
                ->max('period_end');
            
            if ($readyForPayout && $readyForPayout->organizer_count > 0) {
                // If there are old bookings and no recent payouts, suggest creating payouts
                if (!$lastPayoutDate || $lastPayoutDate < now()->subDays(7)->toDateString()) {
                    $insights[] = [
                        'type' => 'info',
                        'icon' => 'heroicon-o-currency-dollar',
                        'message' => "{$readyForPayout->organizer_count} organizers have KES " . number_format($readyForPayout->total, 0) . " in bookings ready for payout",
                        'action' => [
                            'label' => 'Review Payouts',
                            'url' => route('filament.admin.resources.payouts.index'),
                        ],
                    ];
                }
            }
            
            // Revenue comparison to last week
            $revenueData = DB::table('bookings')
                ->selectRaw('
                    SUM(CASE WHEN created_at >= ? THEN total_amount ELSE 0 END) as current_week,
                    SUM(CASE WHEN created_at BETWEEN ? AND ? THEN total_amount ELSE 0 END) as last_week
                ', [
                    now()->startOfWeek(),
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek()
                ])
                ->where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subWeeks(2)->startOfWeek())
                ->first();
            
            if ($revenueData->last_week > 0) {
                $revenueChange = round((($revenueData->current_week - $revenueData->last_week) / $revenueData->last_week) * 100, 1);
                
                if (abs($revenueChange) > 20) {
                    $insights[] = [
                        'type' => $revenueChange > 0 ? 'success' : 'danger',
                        'icon' => $revenueChange > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down',
                        'message' => "Revenue is " . ($revenueChange > 0 ? "up" : "down") . " " . abs($revenueChange) . "% compared to last week",
                        'action' => [
                            'label' => 'View Analytics',
                            'url' => '#',
                        ],
                    ];
                }
            }
            
            // Check for high payment failure rate today
            $todayStats = DB::table('bookings')
                ->whereDate('created_at', today())
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN payment_status = \'failed\' THEN 1 END) as failed,
                    MAX(payment_method) as top_method
                ')
                ->first();
            
            if ($todayStats->total > 10) {
                $failureRate = ($todayStats->failed / $todayStats->total) * 100;
                
                if ($failureRate > 15) {
                    $insights[] = [
                        'type' => 'danger',
                        'icon' => 'heroicon-o-x-circle',
                        'message' => "Payment gateway has " . round($failureRate, 1) . "% failure rate today",
                        'action' => [
                            'label' => 'Check Gateway',
                            'url' => '#',
                        ],
                    ];
                }
            }
            
            // Check for unverified organizers waiting > 24 hours
            $unverifiedOrganizers = DB::table('organizers')
                ->where('is_verified', false)
                ->where('created_at', '<', now()->subDay())
                ->count();
            
            if ($unverifiedOrganizers > 0) {
                $insights[] = [
                    'type' => 'info',
                    'icon' => 'heroicon-o-clock',
                    'message' => "{$unverifiedOrganizers} organizers waiting for verification >24 hours",
                    'action' => [
                        'label' => 'Review Now',
                        'url' => route('filament.admin.resources.organizers.index'),
                    ],
                ];
            }
            
            // Events starting soon without enough ticket sales
            $upcomingEvents = DB::table('events')
                ->where('status', 'published')
                ->whereBetween('event_date', [now(), now()->addDays(3)])
                ->whereRaw('tickets_sold < capacity * 0.3')
                ->count();
            
            if ($upcomingEvents > 0) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'heroicon-o-ticket',
                    'message' => "{$upcomingEvents} events starting soon with <30% tickets sold",
                    'action' => [
                        'label' => 'View Events',
                        'url' => route('filament.admin.resources.events.index'),
                    ],
                ];
            }
            
            return array_slice($insights, 0, 3); // Return max 3 insights
        });
    }
    
    public function shouldDisplay(): bool
    {
        return count($this->getInsights()) > 0;
    }
}