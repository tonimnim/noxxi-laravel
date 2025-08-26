<?php

namespace App\Filament\Organizer\Resources\PayoutResource\Widgets;

use App\Models\Payout;
use App\Services\AvailableBalanceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class PayoutStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $organizer = auth()->user()->organizer;

        if (! $organizer) {
            return [];
        }

        $balanceService = app(AvailableBalanceService::class);
        $balance = $balanceService->getAvailableBalance($organizer);

        $stats = Cache::remember("organizer_payout_stats_{$organizer->id}", 300, function () use ($organizer) {
            return [
                'total_paid' => Payout::where('organizer_id', $organizer->id)
                    ->whereIn('status', ['completed', 'paid'])
                    ->sum('net_amount'),
                'pending' => Payout::where('organizer_id', $organizer->id)
                    ->whereIn('status', ['pending', 'approved', 'processing'])
                    ->sum('net_amount'),
                'last_payout' => Payout::where('organizer_id', $organizer->id)
                    ->whereIn('status', ['completed', 'paid'])
                    ->latest('completed_at')
                    ->value('completed_at'),
            ];
        });

        $currency = $balance['currency'];

        return [
            Stat::make('Available Balance', $currency.' '.number_format($balance['available_balance'], 2))
                ->description('Ready for payout')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Pending Payouts', $currency.' '.number_format($stats['pending'], 2))
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Paid Out', $currency.' '.number_format($stats['total_paid'], 2))
                ->description($stats['last_payout']
                    ? 'Last: '.\Carbon\Carbon::parse($stats['last_payout'])->diffForHumans()
                    : 'No payouts yet')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),
        ];
    }
}
