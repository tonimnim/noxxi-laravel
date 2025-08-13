<?php

namespace App\Filament\Organizer\Widgets;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $organizerId = Auth::user()->organizer?->id;
        if (!$organizerId) {
            return [
                Stat::make('Gross revenue', 'KES 0')
                    ->description('0% increase')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),
                    
                Stat::make('Tickets sold', '0')
                    ->description('0% increase')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('success'),
                    
                Stat::make('Active listings', '0')
                    ->description('Currently published')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color('primary'),
                    
                Stat::make('Upcoming', '0')
                    ->description('Next 7 days')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),
            ];
        }
        $startDate = Carbon::now()->subDays(30);
        
        // Gross Revenue
        $revenue = Booking::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->sum('total_amount');
        
        $previousRevenue = Booking::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [
                $startDate->copy()->subDays(30),
                $startDate
            ])
            ->sum('total_amount');
        
        $revenueChange = $previousRevenue > 0 
            ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;
        
        // Tickets Sold
        $ticketsSold = Ticket::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['valid', 'used'])
            ->count();
        
        $previousTickets = Ticket::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
            ->whereBetween('created_at', [
                $startDate->copy()->subDays(30),
                $startDate
            ])
            ->whereIn('status', ['valid', 'used'])
            ->count();
        
        $ticketsChange = $previousTickets > 0
            ? round((($ticketsSold - $previousTickets) / $previousTickets) * 100, 1)
            : 0;
        
        // Active Listings
        $activeListings = Event::where('organizer_id', $organizerId)
            ->where('status', 'published')
            ->where('event_date', '>=', Carbon::now())
            ->count();
        
        // Upcoming Events (Next 7 days)
        $upcomingEvents = Event::where('organizer_id', $organizerId)
            ->where('status', 'published')
            ->whereBetween('event_date', [
                Carbon::now(),
                Carbon::now()->addDays(7)
            ])
            ->count();
        
        return [
            Stat::make('Gross revenue', 'KES ' . number_format($revenue, 0))
                ->description($revenueChange >= 0 
                    ? $revenueChange . '% increase' 
                    : abs($revenueChange) . '% decrease')
                ->descriptionIcon($revenueChange >= 0 
                    ? 'heroicon-m-arrow-trending-up' 
                    : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger'),
                
            Stat::make('Tickets sold', number_format($ticketsSold))
                ->description($ticketsChange >= 0 
                    ? $ticketsChange . '% increase' 
                    : abs($ticketsChange) . '% decrease')
                ->descriptionIcon($ticketsChange >= 0 
                    ? 'heroicon-m-arrow-trending-up' 
                    : 'heroicon-m-arrow-trending-down')
                ->color($ticketsChange >= 0 ? 'success' : 'danger'),
                
            Stat::make('Active listings', $activeListings)
                ->description('Currently published')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
                
            Stat::make('Upcoming', $upcomingEvents)
                ->description('Next 7 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
    
    protected function getRevenueChart(): array
    {
        $data = [];
        $organizerId = Auth::user()->organizer?->id;
        if (!$organizerId) {
            return $data;
        }
        
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = Booking::whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
            $data[] = $revenue;
        }
        return $data;
    }
    
    protected function getTicketsChart(): array
    {
        $data = [];
        $organizerId = Auth::user()->organizer?->id;
        if (!$organizerId) {
            return $data;
        }
        
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $tickets = Ticket::whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
                ->whereDate('created_at', $date)
                ->whereIn('status', ['valid', 'used'])
                ->count();
            $data[] = $tickets;
        }
        return $data;
    }
}