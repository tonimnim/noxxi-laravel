<?php

namespace App\Filament\Organizer\Resources\BookingResource\Tables;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class BookingTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getColumns())
            ->filters(static::getFilters(), layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->actions(static::getActions())
            ->bulkActions(static::getBulkActions())
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Search by reference, customer name or email...')
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->deferLoading()
            ->poll('60s')
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->recordClasses(fn (Booking $record) => match ($record->payment_status) {
                'refunded' => 'opacity-75',
                'failed' => 'bg-red-50 dark:bg-red-900/10',
                default => 'hover:bg-gray-50 dark:hover:bg-gray-800/50'
            });
    }

    protected static function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('booking_reference')
                ->label('Reference')
                ->searchable()
                ->sortable()
                ->copyable()
                ->copyMessage('Reference copied')
                ->description(fn (Booking $record): string => $record->customer_name."\n".
                    $record->customer_email
                )
                ->wrap()
                ->extraAttributes(['class' => 'font-mono text-xs']),

            Tables\Columns\TextColumn::make('event.title')
                ->label('Listing')
                ->searchable()
                ->sortable()
                ->description(fn (Booking $record): HtmlString => new HtmlString(
                    $record->event->venue_name.'<br>'.
                    '<span class="text-gray-500">'.
                    Carbon::parse($record->event->event_date)->format('M d, Y • g:i A').
                    '</span>'
                )
                )
                ->wrap()
                ->toggleable(),

            Tables\Columns\TextColumn::make('quantity')
                ->label('Tickets')
                ->sortable()
                ->alignCenter()
                ->formatStateUsing(fn (Booking $record): string => $record->quantity.' '.str('ticket')->plural($record->quantity)
                )
                ->description(fn (Booking $record): ?string => $record->ticket_types ?
                    collect($record->ticket_types)
                        ->map(fn ($type) => $type['quantity'].'x '.$type['name'])
                        ->join(', ') : null
                )
                ->toggleable(),

            Tables\Columns\TextColumn::make('total_amount')
                ->label('Gross Amount')
                ->sortable()
                ->formatStateUsing(fn (Booking $record): string => $record->currency.' '.number_format($record->total_amount, 2)
                )
                ->description('What customer paid')
                ->color('gray')
                ->toggleable(),

            Tables\Columns\TextColumn::make('paystack_fee')
                ->label('Processing Fee')
                ->formatStateUsing(function (Booking $record): string {
                    // Calculate Paystack fee (1.5% for M-Pesa, 2.9% for cards)
                    $rate = match ($record->payment_method) {
                        'mpesa' => 0.015,
                        'card', 'apple' => 0.029,
                        default => 0.015,
                    };
                    $fee = $record->total_amount * $rate;

                    return '-'.$record->currency.' '.number_format($fee, 2);
                })
                ->description(fn (Booking $record): string => match ($record->payment_method) {
                    'mpesa' => '1.5% M-Pesa',
                    'card' => '2.9% Card',
                    'apple' => '2.9% Apple Pay',
                    default => 'Processing fee',
                }
                )
                ->color('danger')
                ->toggleable()
                ->toggledHiddenByDefault(),

            Tables\Columns\TextColumn::make('subtotal')
                ->label('Your Revenue')
                ->sortable()
                ->formatStateUsing(function (Booking $record): string {
                    // Revenue after Paystack fees
                    $rate = match ($record->payment_method) {
                        'mpesa' => 0.015,
                        'card', 'apple' => 0.029,
                        default => 0.015,
                    };
                    $paystackFee = $record->total_amount * $rate;
                    $revenue = $record->total_amount - $paystackFee;

                    return $record->currency.' '.number_format($revenue, 2);
                })
                ->description('After processing fees')
                ->color(fn (Booking $record): string => $record->payment_status === 'paid' ? 'success' : 'gray'
                )
                ->weight('bold')
                ->toggleable(),

            Tables\Columns\TextColumn::make('commission')
                ->label('Commission')
                ->formatStateUsing(function (Booking $record): string {
                    // Get commission rate for this event
                    $commissionRate = $record->event->commission_rate ??
                                    $record->event->organizer->commission_rate ??
                                    10.0;
                    $commission = $record->total_amount * ($commissionRate / 100);

                    return '-'.$record->currency.' '.number_format($commission, 2);
                })
                ->description(function (Booking $record): string {
                    $commissionRate = $record->event->commission_rate ??
                                    $record->event->organizer->commission_rate ??
                                    10.0;

                    return $commissionRate.'% platform fee';
                })
                ->color('warning')
                ->toggleable()
                ->toggledHiddenByDefault(),

            Tables\Columns\TextColumn::make('net_amount')
                ->label('Net Amount')
                ->formatStateUsing(function (Booking $record): string {
                    // Calculate all deductions
                    $rate = match ($record->payment_method) {
                        'mpesa' => 0.015,
                        'card', 'apple' => 0.029,
                        default => 0.015,
                    };
                    $paystackFee = $record->total_amount * $rate;

                    $commissionRate = $record->event->commission_rate ??
                                    $record->event->organizer->commission_rate ??
                                    10.0;
                    $commission = $record->total_amount * ($commissionRate / 100);

                    $netAmount = $record->total_amount - $paystackFee - $commission;

                    return $record->currency.' '.number_format($netAmount, 2);
                })
                ->description('Final amount after all fees')
                ->color('success')
                ->weight('bold')
                ->toggleable()
                ->toggledHiddenByDefault(),

            Tables\Columns\TextColumn::make('payment_status')
                ->label('Payment')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'processing' => 'warning',
                    'unpaid' => 'gray',
                    'failed' => 'danger',
                    'refunded', 'partial_refund' => 'info',
                    default => 'gray',
                })
                ->icon(fn (string $state): string => match ($state) {
                    'paid' => 'heroicon-m-check-circle',
                    'processing' => 'heroicon-m-clock',
                    'unpaid' => 'heroicon-m-x-circle',
                    'failed' => 'heroicon-m-exclamation-circle',
                    'refunded' => 'heroicon-m-arrow-uturn-left',
                    default => 'heroicon-m-question-mark-circle',
                })
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    'cancelled' => 'danger',
                    'expired' => 'gray',
                    'refunded' => 'info',
                    default => 'gray',
                })
                ->sortable()
                ->searchable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('payment_method')
                ->label('Method')
                ->badge()
                ->color('gray')
                ->formatStateUsing(fn (string $state): string => str($state)->headline())
                ->toggleable()
                ->toggledHiddenByDefault(),

            Tables\Columns\TextColumn::make('customer_phone')
                ->label('Phone')
                ->searchable()
                ->copyable()
                ->toggleable()
                ->toggledHiddenByDefault(),

            Tables\Columns\TextColumn::make('booking_source')
                ->label('Source')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'mobile_app' => 'success',
                    'web' => 'primary',
                    'api' => 'warning',
                    'admin' => 'danger',
                    default => 'gray',
                })
                ->toggleable()
                ->toggledHiddenByDefault(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('M d, Y')
                ->description(fn (Booking $record): string => $record->created_at->format('g:i A')
                )
                ->sortable()
                ->toggleable(),

            Tables\Columns\IconColumn::make('tickets_checked_in')
                ->label('Check-in')
                ->getStateUsing(fn (Booking $record): bool => $record->tickets()->where('checked_in', true)->exists()
                )
                ->boolean()
                ->trueIcon('heroicon-o-check-badge')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('gray')
                ->alignCenter()
                ->toggleable()
                ->toggledHiddenByDefault(),
        ];
    }

    protected static function getFilters(): array
    {
        return [
            Tables\Filters\Filter::make('date')
                ->label('Date Range')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label('From')
                        ->displayFormat('M d, Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->maxDate(fn (callable $get) => $get('until') ?: now()),
                    Forms\Components\DatePicker::make('until')
                        ->label('Until')
                        ->displayFormat('M d, Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->minDate(fn (callable $get) => $get('from')),
                ])
                ->columns(2)
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): ?string {
                    if (($data['from'] ?? null) && ($data['until'] ?? null)) {
                        return Carbon::parse($data['from'])->format('M d').' - '.Carbon::parse($data['until'])->format('M d, Y');
                    }
                    if ($data['from'] ?? null) {
                        return 'From '.Carbon::parse($data['from'])->format('M d, Y');
                    }
                    if ($data['until'] ?? null) {
                        return 'Until '.Carbon::parse($data['until'])->format('M d, Y');
                    }

                    return null;
                }),

            Tables\Filters\SelectFilter::make('payment_status')
                ->label('Payment Status')
                ->multiple()
                ->options([
                    'paid' => 'Paid',
                    'processing' => 'Processing',
                    'unpaid' => 'Unpaid',
                    'failed' => 'Failed',
                    'refunded' => 'Refunded',
                ])
                ->placeholder('All payments'),

            Tables\Filters\SelectFilter::make('status')
                ->label('Booking Status')
                ->multiple()
                ->options([
                    'confirmed' => 'Confirmed',
                    'pending' => 'Pending',
                    'cancelled' => 'Cancelled',
                    'expired' => 'Expired',
                ])
                ->placeholder('All bookings'),

            Tables\Filters\SelectFilter::make('event_id')
                ->label('Listing')
                ->relationship('event', 'title', fn (Builder $query) => $query->where('organizer_id', auth()->user()->organizer?->id)
                    ->orderBy('event_date', 'desc')
                )
                ->searchable()
                ->preload()
                ->placeholder('All listings'),
        ];
    }

    protected static function getActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye'),

                Tables\Actions\Action::make('download_tickets')
                    ->label('Download Tickets')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (Booking $record) => static::downloadTickets($record))
                    ->visible(fn (Booking $record): bool => $record->payment_status === 'paid' && $record->status === 'confirmed'
                    ),

                Tables\Actions\Action::make('resend_email')
                    ->label('Resend Email')
                    ->icon('heroicon-m-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (Booking $record) => static::resendEmail($record))
                    ->visible(fn (Booking $record): bool => $record->payment_status === 'paid'
                    ),

                Tables\Actions\Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Refund Booking')
                    ->modalDescription('Are you sure you want to refund this booking? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, refund')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Refund Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(fn (Booking $record, array $data) => static::refundBooking($record, $data))
                    ->visible(fn (Booking $record): bool => $record->payment_status === 'paid' &&
                        ! in_array($record->status, ['refunded', 'cancelled'])
                    ),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-m-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Booking $record) => static::cancelBooking($record))
                    ->visible(fn (Booking $record): bool => $record->status === 'pending'
                    ),
            ]),
        ];
    }

    protected static function getBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('export')
                    ->label('Export to CSV')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(fn ($records) => static::exportBookings($records))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('send_reminders')
                    ->label('Send Reminders')
                    ->icon('heroicon-m-bell')
                    ->requiresConfirmation()
                    ->action(fn ($records) => static::sendBulkReminders($records))
                    ->deselectRecordsAfterCompletion(),
            ]),
        ];
    }

    // Action methods would be implemented here
    protected static function downloadTickets(Booking $booking): void
    {
        // Implementation for downloading tickets
    }

    protected static function resendEmail(Booking $booking): void
    {
        // Implementation for resending email
    }

    protected static function refundBooking(Booking $booking, array $data): void
    {
        // Implementation for refunding
    }

    protected static function cancelBooking(Booking $booking): void
    {
        // Implementation for cancelling
    }

    protected static function exportBookings($records): void
    {
        // Implementation for export
    }

    protected static function sendBulkReminders($records): void
    {
        // Implementation for bulk reminders
    }
}
