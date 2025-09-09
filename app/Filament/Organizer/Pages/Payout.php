<?php

namespace App\Filament\Organizer\Pages;

use App\Models\Payout as PayoutModel;
use App\Models\Transaction;
use App\Services\AvailableBalanceService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class Payout extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payouts';
    
    protected static ?string $slug = 'payout';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = true; 

    protected static string $view = 'filament.organizer.pages.payout';

    public function getHeading(): string
    {
        return ''; // Remove the heading
    }

    public function getSubheading(): ?string
    {
        return null; // Remove the subheading
    }

    protected function getViewData(): array
    {
        $organizer = auth()->user()?->organizer;
        
        // Return empty data if no organizer
        if (!$organizer) {
            return [
                'balanceData' => [
                    'currency' => 'KES',
                    'available_balance' => 0,
                    'gross_revenue' => 0,
                    'total_refunds' => 0,
                    'total_commission' => 0,
                    'net_revenue' => 0,
                    'total_paid_out' => 0,
                    'pending_payouts' => 0,
                    'amount_on_hold' => 0,
                ],
                'stats' => [
                    'pending_payouts' => 0,
                    'total_paid_out' => 0,
                    'payout_count' => 0,
                    'this_month_earnings' => 0,
                ],
            ];
        }

        // Get balance data from service
        $balanceService = new AvailableBalanceService();
        $balanceData = $balanceService->getAvailableBalance($organizer);

        // Get payout statistics
        $stats = $this->getPayoutStats($organizer);

        return [
            'balanceData' => $balanceData,
            'stats' => $stats,
        ];
    }

    private function getPayoutStats($organizer): array
    {
        $stats = PayoutModel::where('organizer_id', $organizer->id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status IN ('pending', 'approved', 'processing') THEN net_amount ELSE 0 END), 0) as pending_payouts,
                COALESCE(SUM(CASE WHEN status IN ('completed', 'paid') THEN net_amount ELSE 0 END), 0) as total_paid_out,
                COUNT(CASE WHEN status IN ('completed', 'paid') THEN 1 END) as payout_count
            ")
            ->first();

        // Get this month's earnings
        $thisMonthEarnings = Transaction::where('organizer_id', $organizer->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_TICKET_SALE)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('net_amount');

        return [
            'pending_payouts' => $stats->pending_payouts ?? 0,
            'total_paid_out' => $stats->total_paid_out ?? 0,
            'payout_count' => $stats->payout_count ?? 0,
            'this_month_earnings' => $thisMonthEarnings ?? 0,
        ];
    }

    public function table(Table $table): Table
    {
        $organizerId = auth()->user()?->organizer?->id;
        
        return $table
            ->query(
                PayoutModel::query()
                    ->when($organizerId, fn($query) => $query->where('organizer_id', $organizerId))
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Amount')
                    ->money('kes')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved', 'processing' => 'info',
                        'completed', 'paid' => 'success',
                        'failed' => 'danger',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => \App\Filament\Organizer\Resources\PayoutResource::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('receipt')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['completed', 'paid']))
                    ->url(fn ($record) => route('organizer.payout.receipt', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->poll('30s')
            ->emptyStateHeading('No Payout History')
            ->emptyStateDescription('Your payout requests will appear here')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}