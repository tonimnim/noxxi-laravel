<?php

namespace App\Filament\Organizer\Widgets;

use App\Models\RefundRequest;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefundMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $organizerId = Auth::user()->organizer?->id;

        if (! $organizerId) {
            return [];
        }

        $cacheKey = "organizer_refund_metrics_{$organizerId}";

        $stats = Cache::remember($cacheKey, 60, function () use ($organizerId) {
            // Get refund statistics
            $refundStats = RefundRequest::query()
                ->whereHas('booking.event', function ($query) use ($organizerId) {
                    $query->where('organizer_id', $organizerId);
                })
                ->selectRaw('
                    status,
                    COUNT(*) as count,
                    SUM(approved_amount) as total_approved,
                    SUM(requested_amount) as total_requested
                ')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            // Get total processed refunds from transactions
            $processedRefunds = Transaction::where('organizer_id', $organizerId)
                ->where('type', Transaction::TYPE_REFUND)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->sum(DB::raw('ABS(amount)'));

            // Get total sales for refund rate calculation
            $totalSales = Transaction::where('organizer_id', $organizerId)
                ->where('type', Transaction::TYPE_TICKET_SALE)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->sum('amount');

            // Calculate refund rate
            $refundRate = $totalSales > 0
                ? round(($processedRefunds / $totalSales) * 100, 2)
                : 0;

            // Get pending refund requests count
            $pendingCount = $refundStats->get(RefundRequest::STATUS_PENDING)?->count ?? 0;
            $pendingAmount = $refundStats->get(RefundRequest::STATUS_PENDING)?->total_requested ?? 0;

            // Get approved but not processed
            $approvedCount = $refundStats->get(RefundRequest::STATUS_APPROVED)?->count ?? 0;
            $approvedAmount = $refundStats->get(RefundRequest::STATUS_APPROVED)?->total_approved ?? 0;

            // Get processing time stats (last 30 days)
            $avgProcessingTime = RefundRequest::query()
                ->whereHas('booking.event', function ($query) use ($organizerId) {
                    $query->where('organizer_id', $organizerId);
                })
                ->where('status', RefundRequest::STATUS_PROCESSED)
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('processed_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (processed_at - created_at))/3600) as avg_hours')
                ->value('avg_hours');

            return [
                'pending_count' => $pendingCount,
                'pending_amount' => $pendingAmount,
                'approved_count' => $approvedCount,
                'approved_amount' => $approvedAmount,
                'processed_refunds' => $processedRefunds,
                'refund_rate' => $refundRate,
                'avg_processing_time' => $avgProcessingTime ? round($avgProcessingTime, 1) : null,
            ];
        });

        $currency = Auth::user()->organizer->default_currency ?? 'KES';

        $result = [];

        // Pending Refunds
        if ($stats['pending_count'] > 0) {
            $result[] = Stat::make('Pending Refunds', $stats['pending_count'])
                ->description($currency.' '.number_format($stats['pending_amount'], 0).' requested')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart($this->getRefundTrend('pending'));
        }

        // Approved Awaiting Processing
        if ($stats['approved_count'] > 0) {
            $result[] = Stat::make('Approved (Not Processed)', $stats['approved_count'])
                ->description($currency.' '.number_format($stats['approved_amount'], 0).' to process')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info');
        }

        // Total Refunds Processed
        $result[] = Stat::make('Total Refunds', $currency.' '.number_format($stats['processed_refunds'], 0))
            ->description('Refund rate: '.$stats['refund_rate'].'%')
            ->descriptionIcon('heroicon-m-arrow-uturn-left')
            ->color($stats['refund_rate'] > 10 ? 'danger' : 'success')
            ->chart($this->getRefundTrend('processed'));

        // Average Processing Time
        if ($stats['avg_processing_time'] !== null) {
            $result[] = Stat::make('Avg Processing Time', $stats['avg_processing_time'].' hrs')
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color($stats['avg_processing_time'] > 48 ? 'danger' : 'success');
        }

        return $result;
    }

    /**
     * Get refund trend data for chart
     */
    private function getRefundTrend(string $type): array
    {
        $organizerId = Auth::user()->organizer?->id;

        if (! $organizerId) {
            return [];
        }

        $cacheKey = "organizer_refund_trend_{$organizerId}_{$type}";

        return Cache::remember($cacheKey, 300, function () use ($organizerId, $type) {
            $query = RefundRequest::query()
                ->whereHas('booking.event', function ($query) use ($organizerId) {
                    $query->where('organizer_id', $organizerId);
                })
                ->where('created_at', '>=', now()->subDays(7));

            if ($type === 'pending') {
                $query->where('status', RefundRequest::STATUS_PENDING);
            } elseif ($type === 'processed') {
                $query->where('status', RefundRequest::STATUS_PROCESSED);
            }

            $data = $query
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count')
                ->toArray();

            // Ensure we have 7 data points
            return array_pad($data, -7, 0);
        });
    }
}
