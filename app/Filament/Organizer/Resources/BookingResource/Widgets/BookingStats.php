<?php

namespace App\Filament\Organizer\Resources\BookingResource\Widgets;

use App\Models\Booking;
use App\Models\Event;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class BookingStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $organizerId = Auth::user()->organizer?->id;
        if (! $organizerId) {
            return $this->getEmptyStats();
        }

        $today = Carbon::today();

        // Today's bookings query
        $todayBookings = Booking::whereHas('event', function ($query) use ($organizerId, $today) {
            $query->where('organizer_id', $organizerId)
                ->whereDate('event_date', $today);
        })->where('payment_status', 'paid');

        // Today's revenue
        $todayRevenue = (clone $todayBookings)->sum('total_amount');
        $todayCount = (clone $todayBookings)->count();

        // Get default currency from organizer
        $currency = Auth::user()->organizer->default_currency ?? 'KES';

        // Pending payments
        $pendingPayments = Booking::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })->whereIn('payment_status', ['unpaid', 'processing'])->count();

        // Check-in rate for today's events
        $todayEvents = Event::where('organizer_id', $organizerId)
            ->whereDate('event_date', $today)
            ->pluck('id');

        $totalTickets = Booking::whereIn('event_id', $todayEvents)
            ->where('payment_status', 'paid')
            ->sum('quantity');

        $checkedInTickets = 0;
        if ($totalTickets > 0) {
            // This would need tickets table to have checked_in column
            // For now, we'll show a placeholder
            $checkInRate = 0;
        } else {
            $checkInRate = 0;
        }

        // This week's revenue
        $weekRevenue = Booking::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])
            ->sum('total_amount');

        // Previous week's revenue for comparison
        $lastWeekRevenue = Booking::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek(),
            ])
            ->sum('total_amount');

        $revenueChange = $lastWeekRevenue > 0
            ? round((($weekRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100, 1)
            : 0;

        return [
            Stat::make("Today's Bookings", $todayCount)
                ->color('primary')
                ->chart($this->getRecentBookingsChart()),

            Stat::make("Today's Revenue", $currency.' '.number_format($todayRevenue, 0))
                ->color('success'),

            Stat::make('Pending Payments', $pendingPayments)
                ->color($pendingPayments > 0 ? 'warning' : 'gray'),

            Stat::make('This Week', $currency.' '.number_format($weekRevenue, 0))
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($this->getWeeklyRevenueChart()),
        ];
    }

    protected function getEmptyStats(): array
    {
        return [
            Stat::make("Today's Bookings", '0')
                ->color('primary'),

            Stat::make("Today's Revenue", 'KES 0')
                ->color('success'),

            Stat::make('Pending Payments', '0')
                ->color('gray'),

            Stat::make('This Week', 'KES 0')
                ->color('success'),
        ];
    }

    protected function getRecentBookingsChart(): array
    {
        $organizerId = Auth::user()->organizer?->id;
        if (! $organizerId) {
            return [0, 0, 0, 0, 0, 0, 0];
        }

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = Booking::whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
                ->whereDate('created_at', $date)
                ->where('payment_status', 'paid')
                ->count();
            $data[] = $count;
        }

        return $data;
    }

    protected function getWeeklyRevenueChart(): array
    {
        $organizerId = Auth::user()->organizer?->id;
        if (! $organizerId) {
            return [0, 0, 0, 0, 0, 0, 0];
        }

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = Booking::whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
                ->whereDate('created_at', $date)
                ->where('payment_status', 'paid')
                ->sum('total_amount');
            $data[] = (int) $revenue;
        }

        return $data;
    }
}
