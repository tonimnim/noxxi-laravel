<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrganizerResource\Pages;
use App\Models\Organizer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrganizerResource extends Resource
{
    protected static ?string $model = Organizer::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Organizers';
    protected static ?int $navigationSort = 2;
    
    // Global search configuration
    protected static ?string $recordTitleAttribute = 'business_name';
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['business_name', 'business_type', 'business_country', 'business_address'];
    }
    
    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->business_name;
    }
    
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Type' => ucfirst(str_replace('_', ' ', $record->business_type)),
            'Country' => $record->business_country ?? 'Not specified',
            'Verified' => $record->is_verified ? 'Yes' : 'No',
            'Active' => $record->is_active ? 'Yes' : 'No',
        ];
    }
    
    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->select(['id', 'business_name', 'business_type', 'business_country', 'is_verified', 'is_active', 'user_id'])
            ->with(['user:id,email,full_name']); // Optimized eager loading
    }
    
    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('admin.organizers.pending_approval', 60, function () {
            return Organizer::where('is_verified', false)->count() ?: null;
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
                Forms\Components\TextInput::make('business_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('business_type')
                    ->options([
                        'individual' => 'Individual',
                        'company' => 'Company',
                        'non_profit' => 'Non Profit',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_verified')
                    ->label('Verified'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active'),
                Forms\Components\TextInput::make('commission_rate')
                    ->numeric()
                    ->suffix('%')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('business_type')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListOrganizers::route('/'),
            'create' => Pages\CreateOrganizer::route('/create'),
            'edit' => Pages\EditOrganizer::route('/{record}/edit'),
        ];
    }
}