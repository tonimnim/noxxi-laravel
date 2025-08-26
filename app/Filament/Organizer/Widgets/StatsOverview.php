<?php

namespace App\Filament\Organizer\Widgets;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected static ?string $pollingInterval = '10s'; // Poll every 10 seconds for real-time updates

    protected function getStats(): array
    {
        $organizerId = Auth::user()->organizer?->id;
        $currency = Auth::user()->organizer?->default_currency ?? 'KES';

        if (! $organizerId) {
            return [
                Stat::make('Gross revenue', $currency.' 0')
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

                Stat::make('Total Refunds', $currency.' 0')
                    ->description('All time')
                    ->descriptionIcon('heroicon-m-arrow-uturn-left')
                    ->color('danger'),
            ];
        }
        $startDate = Carbon::now()->subDays(30);

        // Gross Revenue (subtract service fees - organizer only gets subtotal)
        $revenue = Booking::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->sum('subtotal');

        $previousRevenue = Booking::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [
                $startDate->copy()->subDays(30),
                $startDate,
            ])
            ->sum('subtotal');

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
                $startDate,
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

        // Total Refunds (all time)
        $totalRefunds = Transaction::where('organizer_id', $organizerId)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum(DB::raw('ABS(amount)'));

        // Get refunds for last 30 days for comparison
        $recentRefunds = Transaction::where('organizer_id', $organizerId)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->sum(DB::raw('ABS(amount)'));

        $previousRefunds = Transaction::where('organizer_id', $organizerId)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [
                $startDate->copy()->subDays(30),
                $startDate,
            ])
            ->sum(DB::raw('ABS(amount)'));

        $refundsChange = $previousRefunds > 0
            ? round((($recentRefunds - $previousRefunds) / $previousRefunds) * 100, 1)
            : ($recentRefunds > 0 ? 100 : 0);

        return [
            Stat::make('Gross revenue', $currency.' '.number_format($revenue, 0))
                ->description($revenueChange >= 0
                    ? $revenueChange.'% increase'
                    : abs($revenueChange).'% decrease')
                ->descriptionIcon($revenueChange >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($this->getRevenueChart()),

            Stat::make('Tickets sold', number_format($ticketsSold))
                ->description($ticketsChange >= 0
                    ? $ticketsChange.'% increase'
                    : abs($ticketsChange).'% decrease')
                ->descriptionIcon($ticketsChange >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($ticketsChange >= 0 ? 'success' : 'danger')
                ->chart($this->getTicketsChart()),

            Stat::make('Active listings', $activeListings)
                ->description('Currently published')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Total Refunds', $currency.' '.number_format($totalRefunds, 0))
                ->description($refundsChange > 0
                    ? $refundsChange.'% increase'
                    : ($refundsChange < 0 ? abs($refundsChange).'% decrease' : 'No change'))
                ->descriptionIcon($refundsChange > 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->color($refundsChange > 0 ? 'warning' : 'success'),
        ];
    }

    protected function getRevenueChart(): array
    {
        $data = [];
        $organizerId = Auth::user()->organizer?->id;
        if (! $organizerId) {
            return $data;
        }

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = Booking::whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('subtotal');
            $data[] = $revenue;
        }

        return $data;
    }

    protected function getTicketsChart(): array
    {
        $data = [];
        $organizerId = Auth::user()->organizer?->id;
        if (! $organizerId) {
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
