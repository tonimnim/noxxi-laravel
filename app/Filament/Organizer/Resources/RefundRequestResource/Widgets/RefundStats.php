<?php

namespace App\Filament\Organizer\Resources\RefundRequestResource\Widgets;

use App\Models\RefundRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RefundStats extends BaseWidget
{
    protected function getStats(): array
    {
        $organizerId = Auth::user()->organizer?->id;

        if (! $organizerId) {
            return [];
        }

        $query = RefundRequest::whereHas('booking.event', function ($q) use ($organizerId) {
            $q->where('organizer_id', $organizerId);
        });

        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $processedCount = (clone $query)->where('status', 'processed')->count();
        $totalRefunded = (clone $query)
            ->where('status', 'processed')
            ->sum('approved_amount');

        // Get currency from the most recent refund
        $currency = (clone $query)->latest()->first()?->currency ?? 'KES';

        return [
            Stat::make('Pending Refunds', $pendingCount)
                ->description('Awaiting review')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Processed Refunds', $processedCount)
                ->description('This month')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Total Refunded', $currency.' '.number_format($totalRefunded, 2))
                ->description('All time')
                ->color('gray')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
