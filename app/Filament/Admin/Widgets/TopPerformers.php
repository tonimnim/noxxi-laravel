<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Event;
use App\Models\Organizer;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopPerformers extends Widget
{
    protected static string $view = 'filament.admin.widgets.top-performers';
    
    protected static ?int $sort = 6;
    
    protected static bool $isLazy = true; // Lazy load
    
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
        'xl' => 1,
    ];
    
    protected static ?string $pollingInterval = null; // Disable polling
    
    public string $activeTab = 'organizers';
    
    public function getTopOrganizers(): array
    {
        return Cache::remember('admin.top_organizers', 300, function () {
            return DB::table('organizers')
                ->join('events', 'organizers.id', '=', 'events.organizer_id')
                ->join('bookings', 'events.id', '=', 'bookings.event_id')
                ->join('transactions', 'bookings.id', '=', 'transactions.booking_id')
                ->select(
                    'organizers.id',
                    'organizers.business_name',
                    DB::raw('SUM(transactions.amount) as total_revenue'),
                    DB::raw('COUNT(DISTINCT events.id) as event_count'),
                    DB::raw('COUNT(DISTINCT bookings.id) as booking_count')
                )
                ->where('transactions.status', 'success')
                ->where('transactions.created_at', '>=', now()->subDays(30))
                ->groupBy('organizers.id', 'organizers.business_name')
                ->orderByDesc('total_revenue')
                ->limit(5)
                ->get()
                ->map(function ($organizer, $index) {
                    return [
                        'rank' => $index + 1,
                        'name' => $organizer->business_name ?? 'Unknown Organizer',
                        'revenue' => $organizer->total_revenue,
                        'revenue_formatted' => 'KES ' . number_format($organizer->total_revenue, 0),
                        'events' => $organizer->event_count,
                        'bookings' => $organizer->booking_count,
                        'metric' => $organizer->event_count . ' events',
                    ];
                })
                ->toArray();
        });
    }
    
    public function getTopEvents(): array
    {
        return Cache::remember('admin.top_events', 300, function () {
            return DB::table('events')
                ->join('bookings', 'events.id', '=', 'bookings.event_id')
                ->leftJoin('tickets', 'bookings.id', '=', 'tickets.booking_id')
                ->select(
                    'events.id',
                    'events.title',
                    'events.event_date',
                    DB::raw('COUNT(DISTINCT bookings.id) as booking_count'),
                    DB::raw('COUNT(tickets.id) as ticket_count'),
                    DB::raw('SUM(bookings.total_amount) as total_revenue')
                )
                ->where('bookings.status', 'confirmed')
                ->where('events.event_date', '>=', now())
                ->groupBy('events.id', 'events.title', 'events.event_date')
                ->orderByDesc('ticket_count')
                ->limit(5)
                ->get()
                ->map(function ($event, $index) {
                    return [
                        'rank' => $index + 1,
                        'name' => $event->title,
                        'tickets' => $event->ticket_count,
                        'tickets_formatted' => number_format($event->ticket_count) . ' tickets',
                        'revenue' => $event->total_revenue,
                        'revenue_formatted' => 'KES ' . number_format($event->total_revenue, 0),
                        'date' => \Carbon\Carbon::parse($event->event_date)->format('M d, Y'),
                        'metric' => number_format($event->ticket_count) . ' tickets',
                    ];
                })
                ->toArray();
        });
    }
}