<?php

namespace App\Filament\Organizer\Resources;

use App\Filament\Organizer\Resources\PayoutResource\Pages;
use App\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Payout History';

    protected static ?int $navigationSort = 8;

    protected static bool $shouldRegisterNavigation = false; // Hide from navigation

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('organizer_id', auth()->user()->organizer->id)
            ->latest();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference Number')
                            ->disabled(),
                        Forms\Components\TextInput::make('status')
                            ->disabled(),
                        Forms\Components\TextInput::make('gross_amount')
                            ->label('Requested Amount')
                            ->prefix('KES')
                            ->disabled(),
                        Forms\Components\TextInput::make('net_amount')
                            ->label('Amount Received')
                            ->prefix('KES')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Processing Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('requested_at')
                            ->label('Request Date')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approval Date')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completion Date')
                            ->disabled(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->disabled()
                            ->visible(fn ($record) => $record?->status === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full' => 'success',
                        'partial' => 'warning',
                        'half' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Requested')
                    ->money('kes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payout_fee')
                    ->label('Fee')
                    ->money('kes')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Received')
                    ->money('kes')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'processing' => 'info',
                        'completed', 'paid' => 'success',
                        'failed' => 'danger',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'mpesa' => 'M-Pesa',
                        'bank' => 'Bank Transfer',
                    ]),
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
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('downloadReceipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn ($record) => in_array($record->status, ['completed', 'paid']))
                    ->url(fn ($record) => route('organizer.payout.receipt', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No Payout History')
            ->emptyStateDescription('Your payout requests will appear here')
            ->emptyStateActions([
                Tables\Actions\Action::make('request')
                    ->label('Go to Dashboard')
                    ->icon('heroicon-o-home')
                    ->url(fn () => '/organizer/dashboard'),
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
            'view' => Pages\ViewPayout::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Payouts are created through the PayoutRequest page
    }

    public static function canEdit($record): bool
    {
        return false; // Organizers cannot edit payouts
    }

    public static function canDelete($record): bool
    {
        return false; // Organizers cannot delete payouts
    }
}
