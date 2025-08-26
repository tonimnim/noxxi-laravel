<?php

namespace App\Filament\Organizer\Resources\RefundRequestResource\Tables;

use App\Models\RefundRequest;
use App\Services\RefundService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class RefundRequestTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getColumns())
            ->filters(static::getFilters(), layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->actions(static::getActions())
            ->bulkActions(static::getBulkActions())
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Search by customer name, email or booking reference...')
            ->paginated([10, 25, 50])
            ->striped()
            ->deferLoading()
            ->poll('60s')
            ->recordClasses(fn (RefundRequest $record) => match ($record->status) {
                'processed' => 'opacity-75 bg-green-50 dark:bg-green-900/10',
                'rejected' => 'opacity-75 bg-red-50 dark:bg-red-900/10',
                'pending' => 'bg-yellow-50 dark:bg-yellow-900/10',
                default => 'hover:bg-gray-50 dark:hover:bg-gray-800/50'
            });
    }

    protected static function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('M d, Y H:i')
                ->sortable()
                ->description(fn (RefundRequest $record): string => Carbon::parse($record->created_at)->diffForHumans()
                ),

            Tables\Columns\TextColumn::make('booking.booking_reference')
                ->label('Booking')
                ->searchable()
                ->copyable()
                ->copyMessage('Reference copied')
                ->description(fn (RefundRequest $record): HtmlString => new HtmlString(
                    '<span class="font-medium">'.$record->booking->customer_name.'</span><br>'.
                    $record->booking->customer_email
                ))
                ->wrap(),

            Tables\Columns\TextColumn::make('booking.event.title')
                ->label('Event')
                ->searchable()
                ->limit(30)
                ->description(fn (RefundRequest $record): string => Carbon::parse($record->booking->event->event_date)->format('M d, Y')
                )
                ->wrap(),

            Tables\Columns\TextColumn::make('requested_amount')
                ->label('Amount Requested')
                ->sortable()
                ->formatStateUsing(fn (RefundRequest $record): string => $record->currency.' '.number_format($record->requested_amount, 2)
                )
                ->description(fn (RefundRequest $record): string => 'Original: '.$record->currency.' '.number_format($record->booking->total_amount, 2)
                )
                ->color('warning'),

            Tables\Columns\TextColumn::make('reason')
                ->label('Reason')
                ->limit(50)
                ->tooltip(fn (RefundRequest $record): string => $record->reason)
                ->wrap(),

            Tables\Columns\BadgeColumn::make('status')
                ->label('Status')
                ->colors([
                    'warning' => 'pending',
                    'info' => 'reviewing',
                    'success' => 'approved',
                    'danger' => 'rejected',
                    'success' => 'processed',
                    'gray' => 'cancelled',
                ])
                ->icons([
                    'heroicon-o-clock' => 'pending',
                    'heroicon-o-eye' => 'reviewing',
                    'heroicon-o-check-circle' => 'approved',
                    'heroicon-o-x-circle' => 'rejected',
                    'heroicon-o-check-badge' => 'processed',
                    'heroicon-o-ban' => 'cancelled',
                ]),

            Tables\Columns\TextColumn::make('days_until_event')
                ->label('Event In')
                ->getStateUsing(function (RefundRequest $record): string {
                    $days = now()->diffInDays($record->booking->event->event_date, false);
                    if ($days < 0) {
                        return 'Event passed';
                    } elseif ($days == 0) {
                        return 'Today';
                    } elseif ($days == 1) {
                        return '1 day';
                    } else {
                        return $days.' days';
                    }
                })
                ->color(fn (RefundRequest $record): string => now()->diffInDays($record->booking->event->event_date, false) < 3 ? 'danger' : 'gray'
                ),
        ];
    }

    protected static function getFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pending' => 'Pending',
                    'reviewing' => 'Reviewing',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'processed' => 'Processed',
                    'cancelled' => 'Cancelled',
                ])
                ->default('pending'),

            Tables\Filters\Filter::make('created_at')
                ->form([
                    Forms\Components\DatePicker::make('created_from')
                        ->label('From'),
                    Forms\Components\DatePicker::make('created_until')
                        ->label('Until'),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['created_from'] ?? null) {
                        $indicators[] = 'From '.Carbon::parse($data['created_from'])->format('M d, Y');
                    }
                    if ($data['created_until'] ?? null) {
                        $indicators[] = 'Until '.Carbon::parse($data['created_until'])->format('M d, Y');
                    }

                    return $indicators;
                }),

            Tables\Filters\TernaryFilter::make('full_refund')
                ->label('Refund Type')
                ->placeholder('All refunds')
                ->trueLabel('Full refunds')
                ->falseLabel('Partial refunds')
                ->queries(
                    true: fn ($query) => $query->whereHas('booking', fn ($q) => $q->whereColumn('refund_requests.requested_amount', '>=', 'bookings.total_amount')),
                    false: fn ($query) => $query->whereHas('booking', fn ($q) => $q->whereColumn('refund_requests.requested_amount', '<', 'bookings.total_amount')),
                ),
        ];
    }

    protected static function getActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->modalHeading('Refund Request Details')
                ->modalWidth('2xl'),

            Tables\Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (RefundRequest $record): bool => in_array($record->status, ['pending', 'reviewing'])
                )
                ->requiresConfirmation()
                ->form([
                    Forms\Components\TextInput::make('approved_amount')
                        ->label('Refund Amount')
                        ->numeric()
                        ->required()
                        ->prefix(fn (RefundRequest $record) => $record->currency)
                        ->default(fn (RefundRequest $record) => $record->requested_amount)
                        ->helperText(fn (RefundRequest $record) => 'Requested: '.$record->currency.' '.number_format($record->requested_amount, 2).
                            ' | Original Payment: '.$record->currency.' '.number_format($record->booking->total_amount, 2)
                        )
                        ->rules([
                            fn (RefundRequest $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                if ($value > $record->booking->total_amount) {
                                    $fail('Refund amount cannot exceed the original payment amount.');
                                }
                            },
                        ]),
                    Forms\Components\Textarea::make('review_notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->placeholder('Optional notes about this refund approval'),
                ])
                ->action(function (RefundRequest $record, array $data): void {
                    DB::beginTransaction();
                    try {
                        // Update refund request
                        $record->approve(
                            admin: auth()->user(),
                            approvedAmount: $data['approved_amount'],
                            notes: $data['review_notes']
                        );

                        // Process the refund via Paystack
                        $refundService = app(RefundService::class);
                        $result = $refundService->processRefund($record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Refund processed successfully')
                                ->body('Amount: '.$record->currency.' '.number_format($data['approved_amount'], 2))
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception($result['message']);
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollback();
                        Notification::make()
                            ->title('Refund processing failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Tables\Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (RefundRequest $record): bool => in_array($record->status, ['pending', 'reviewing'])
                )
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3)
                        ->placeholder('Explain why this refund is being rejected'),
                    Forms\Components\Textarea::make('customer_response')
                        ->label('Message to Customer')
                        ->rows(3)
                        ->placeholder('Optional message to send to the customer'),
                ])
                ->action(function (RefundRequest $record, array $data): void {
                    $record->reject(
                        admin: auth()->user(),
                        reason: $data['rejection_reason'],
                        customerResponse: $data['customer_response']
                    );

                    Notification::make()
                        ->title('Refund request rejected')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected static function getBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                // Export can be added later if needed
            ]),
        ];
    }
}
