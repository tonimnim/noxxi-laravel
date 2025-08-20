<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $modelLabel = 'Transaction';
    protected static ?string $pluralModelLabel = 'Transactions';
    
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Transactions';
    protected static ?int $navigationSort = 5;
    
    // Global search configuration
    protected static ?string $recordTitleAttribute = 'booking_reference';
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['booking_reference', 'payment_reference', 'user.full_name', 'user.email'];
    }
    
    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->booking_reference;
    }
    
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Customer' => $record->user?->full_name ?? 'N/A',
            'Amount' => 'KES ' . number_format($record->total_amount, 2),
            'Status' => ucfirst($record->payment_status),
            'Date' => $record->created_at?->format('M d, Y'),
        ];
    }
    
    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->select(['id', 'booking_reference', 'payment_reference', 'total_amount', 'payment_status', 'created_at', 'user_id', 'event_id'])
            ->with(['user:id,full_name,email', 'event:id,title']); // Optimized eager loading
    }
    
    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('admin.transactions.failed', 60, function () {
            return Booking::where('payment_status', 'failed')->count() ?: null;
        });
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'danger' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('booking_reference')
                    ->disabled(),
                Forms\Components\Select::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->prefix('KES')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Event')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'processing' => 'warning',
                        'unpaid' => 'gray',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
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
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}