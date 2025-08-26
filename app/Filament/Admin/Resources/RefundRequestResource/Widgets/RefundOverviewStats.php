<?php

namespace App\Filament\Admin\Resources\RefundRequestResource\Widgets;

use App\Models\RefundRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RefundOverviewStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        // Cache these queries for performance
        $stats = cache()->remember('admin.refund.stats', 60, function () {
            return [
                'total_pending' => RefundRequest::where('status', 'pending')->count(),
                'total_processed' => RefundRequest::where('status', 'processed')->count(),
                'total_refunded' => RefundRequest::where('status', 'processed')->sum('approved_amount'),
                'this_month' => RefundRequest::where('status', 'processed')
                    ->whereMonth('processed_at', now()->month)
                    ->whereYear('processed_at', now()->year)
                    ->sum('approved_amount'),
                'avg_processing_time' => RefundRequest::where('status', 'processed')
                    ->whereNotNull('processed_at')
                    ->selectRaw('AVG(EXTRACT(EPOCH FROM (processed_at - created_at))/3600) as avg_hours')
                    ->value('avg_hours'),
                'top_organizer' => RefundRequest::join('bookings', 'refund_requests.booking_id', '=', 'bookings.id')
                    ->join('events', 'bookings.event_id', '=', 'events.id')
                    ->join('organizers', 'events.organizer_id', '=', 'organizers.id')
                    ->where('refund_requests.status', 'processed')
                    ->select('organizers.business_name', DB::raw('COUNT(*) as refund_count'))
                    ->groupBy('organizers.id', 'organizers.business_name')
                    ->orderByDesc('refund_count')
                    ->first(),
            ];
        });

        $avgProcessingTime = $stats['avg_processing_time']
            ? round($stats['avg_processing_time']).' hours'
            : 'N/A';

        $topOrganizerDesc = $stats['top_organizer']
            ? $stats['top_organizer']->business_name.' ('.$stats['top_organizer']->refund_count.' refunds)'
            : 'No refunds yet';

        return [
            Stat::make('Pending Refunds', $stats['total_pending'])
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->url(fn () => route('filament.admin.resources.refund-requests.index', [
                    'tableFilters[status][values][0]' => 'pending',
                ])),

            Stat::make('Total Processed', $stats['total_processed'])
                ->description('All time')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Refunded', 'KES '.number_format($stats['total_refunded'], 2))
                ->description('This month: KES '.number_format($stats['this_month'], 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Avg Processing Time', $avgProcessingTime)
                ->description($topOrganizerDesc)
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('gray'),
        ];
    }
}
