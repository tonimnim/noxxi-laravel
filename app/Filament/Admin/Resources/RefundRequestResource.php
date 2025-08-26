<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RefundRequestResource\Pages;
use App\Filament\Admin\Resources\RefundRequestResource\Widgets\RefundOverviewStats;
use App\Models\RefundRequest;
use App\Services\RefundService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class RefundRequestResource extends Resource
{
    protected static ?string $model = RefundRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Refunds';

    protected static ?string $modelLabel = 'Refund Request';

    protected static ?string $pluralModelLabel = 'Refund Requests';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('admin.refunds.pending', 60, function () {
            return RefundRequest::where('status', 'pending')->count() ?: null;
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Refund Information')
                    ->schema([
                        Forms\Components\TextInput::make('booking.booking_reference')
                            ->label('Booking Reference')
                            ->disabled(),
                        Forms\Components\TextInput::make('user.full_name')
                            ->label('Customer')
                            ->disabled(),
                        Forms\Components\TextInput::make('booking.event.title')
                            ->label('Event')
                            ->disabled(),
                        Forms\Components\TextInput::make('booking.event.organizer.business_name')
                            ->label('Organizer')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Refund Details')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Refund Reason')
                            ->disabled()
                            ->rows(3),
                        Forms\Components\TextInput::make('requested_amount')
                            ->label('Requested Amount')
                            ->numeric()
                            ->prefix(fn ($record) => $record?->currency ?? 'KES')
                            ->disabled(),
                        Forms\Components\TextInput::make('approved_amount')
                            ->label('Approved Amount')
                            ->numeric()
                            ->prefix(fn ($record) => $record?->currency ?? 'KES')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'reviewing' => 'Reviewing',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'processed' => 'Processed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Processing Information')
                    ->schema([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->disabled(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(3)
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Requested At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Processed At')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->description(fn ($record): string => Carbon::parse($record->created_at)->diffForHumans()
                    ),

                Tables\Columns\TextColumn::make('booking.booking_reference')
                    ->label('Booking')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Reference copied'),

                Tables\Columns\TextColumn::make('booking.event.organizer.business_name')
                    ->label('Organizer')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->tooltip(fn ($record): string => $record->booking->event->organizer->business_name
                    ),

                Tables\Columns\TextColumn::make('booking.event.title')
                    ->label('Event')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn ($record): string => $record->booking->event->title
                    ),

                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->user->email
                    ),

                Tables\Columns\TextColumn::make('requested_amount')
                    ->label('Amount')
                    ->sortable()
                    ->formatStateUsing(fn ($record): string => $record->currency.' '.number_format($record->requested_amount, 2)
                    )
                    ->color('warning'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'reviewing',
                        'success' => ['approved', 'processed'],
                        'danger' => ['rejected', 'failed'],
                        'gray' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not processed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'reviewing' => 'Reviewing',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'processed' => 'Processed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('organizer')
                    ->relationship('booking.event.organizer', 'business_name')
                    ->searchable()
                    ->preload()
                    ->label('Organizer'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
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

                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->numeric()
                            ->label('Min Amount'),
                        Forms\Components\TextInput::make('amount_to')
                            ->numeric()
                            ->label('Max Amount'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('requested_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('requested_amount', '<=', $amount),
                            );
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('process')
                    ->label('Process')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'reviewing'])
                    )
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('approved_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->required()
                            ->prefix(fn ($record) => $record->currency)
                            ->default(fn ($record) => $record->requested_amount)
                            ->helperText(fn ($record) => 'Requested: '.$record->currency.' '.number_format($record->requested_amount, 2)
                            ),
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Internal Notes')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        DB::beginTransaction();
                        try {
                            $record->approve(
                                admin: auth()->user(),
                                approvedAmount: $data['approved_amount'],
                                notes: $data['review_notes']
                            );

                            $refundService = app(RefundService::class);
                            $result = $refundService->processRefund($record);

                            if ($result['success']) {
                                Notification::make()
                                    ->title('Refund processed successfully')
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            $csv = Writer::createFromString('');

                            // Add headers
                            $csv->insertOne([
                                'Refund ID',
                                'Date',
                                'Booking Reference',
                                'Organizer',
                                'Event',
                                'Customer',
                                'Email',
                                'Requested Amount',
                                'Approved Amount',
                                'Status',
                                'Reason',
                                'Processed Date',
                            ]);

                            // Add data
                            foreach ($records as $record) {
                                $csv->insertOne([
                                    $record->id,
                                    $record->created_at->format('Y-m-d H:i:s'),
                                    $record->booking->booking_reference,
                                    $record->booking->event->organizer->business_name,
                                    $record->booking->event->title,
                                    $record->user->full_name,
                                    $record->user->email,
                                    $record->currency.' '.number_format($record->requested_amount, 2),
                                    $record->approved_amount ? $record->currency.' '.number_format($record->approved_amount, 2) : '',
                                    $record->status,
                                    $record->reason,
                                    $record->processed_at?->format('Y-m-d H:i:s') ?? '',
                                ]);
                            }

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv->toString();
                            }, 'refunds-'.now()->format('Y-m-d-His').'.csv');
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Search by booking ref, customer name, email...')
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->poll('60s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'booking' => function ($query) {
                    $query->with(['event' => function ($q) {
                        $q->with('organizer');
                    }, 'user']);
                },
                'user',
                'transaction',
                'reviewedBy',
                'processedBy',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefundRequests::route('/'),
            'view' => Pages\ViewRefundRequest::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            RefundOverviewStats::class,
        ];
    }
}
