<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PayoutResource\Pages;
use App\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('admin.payouts.pending', 60, function () {
            return Payout::where('status', 'pending')->count() ?: null;
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
                Forms\Components\Section::make('Payout Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->disabled(),
                        Forms\Components\Select::make('organizer_id')
                            ->relationship('organizer', 'business_name')
                            ->disabled()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'on_hold' => 'On Hold',
                                'approved' => 'Approved',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'full' => 'Full Payout',
                                'partial' => 'Partial Payout',
                            ])
                            ->default('full')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('gross_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->helperText('Gross payout amount'),
                        Forms\Components\TextInput::make('commission_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->helperText('Platform commission deducted'),
                        Forms\Components\TextInput::make('payout_fee')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->helperText('Payout processing fee'),
                        Forms\Components\TextInput::make('net_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->helperText('Final amount to be paid'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Period & Notes')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->required(),
                        Forms\Components\DatePicker::make('period_end')
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->rows(3)
                            ->maxLength(1000)
                            ->visible(fn ($get) => $get('status') === 'rejected'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('organizer.business_name')
                    ->label('Organizer')
                    ->searchable()
                    ->description(fn ($record) => $record->organizer->status === 'premium' ? 'Premium' : 'Normal')
                    ->color(fn ($record) => $record->organizer->status === 'premium' ? 'success' : null),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full' => 'success',
                        'partial' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Gross Amount')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net Payout')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('period_start')
                    ->date()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('period_end')
                    ->date()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'full' => 'Full Payout',
                        'partial' => 'Partial Payout',
                    ]),
                Tables\Filters\SelectFilter::make('organizer')
                    ->relationship('organizer', 'business_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'on_hold']))
                    ->requiresConfirmation()
                    ->modalHeading('Accept Payout')
                    ->modalDescription(fn ($record) => 'Accept and process payout of '.($record->currency ?? 'KES').' '.number_format($record->net_amount, 2).
                        ' to '.$record->organizer->business_name.'?'
                    )
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes (optional)')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update([
                            'status' => 'approved',
                            'admin_notes' => $data['admin_notes'] ?? null,
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                        ]);

                        // Send notification to organizer
                        $record->organizer->user->notify(new \App\Notifications\PayoutApprovedNotification($record));
                        
                        // Dispatch payout processing job to release money
                        \App\Jobs\ProcessPayoutJob::dispatch($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Payout accepted')
                            ->body('Payout has been accepted and will be processed to the organizer\'s '.ucfirst($record->payment_method).' account.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('hold')
                    ->label('Hold')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Put Payout On Hold')
                    ->form([
                        Forms\Components\Textarea::make('hold_reason')
                            ->label('Reason for Hold')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('This reason will be sent to the organizer'),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update([
                            'status' => 'on_hold',
                            'hold_reason' => $data['hold_reason'],
                            'held_at' => now(),
                            'held_by' => auth()->id(),
                        ]);
                        
                        // Send notification to organizer about the hold
                        $record->organizer->user->notify(new \App\Notifications\PayoutOnHoldNotification($record, $data['hold_reason']));

                        \Filament\Notifications\Notification::make()
                            ->title('Payout put on hold')
                            ->body('Payout has been placed on hold and organizer has been notified.')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('markCompleted')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'processing')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update([
                            'status' => 'completed',
                            'transaction_reference' => $data['transaction_reference'],
                            'completed_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Payout completed')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('acceptSelected')
                        ->label('Accept Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $approved = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'on_hold'])) {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_at' => now(),
                                        'approved_by' => auth()->id(),
                                    ]);
                                    // Send notification to organizer
                                    $record->organizer->user->notify(new \App\Notifications\PayoutApprovedNotification($record));
                                    // Dispatch payout processing
                                    \App\Jobs\ProcessPayoutJob::dispatch($record);
                                    $approved++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title($approved.' payouts accepted and processing')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'view' => Pages\ViewPayout::route('/{record}'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
