<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Events';

    protected static ?int $navigationSort = 4;

    // Global search configuration
    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'venue_name', 'city'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Venue' => $record->venue_name,
            'City' => $record->city,
            'Date' => $record->event_date?->format('M d, Y'),
            'Status' => ucfirst($record->status),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->select(['id', 'title', 'venue_name', 'city', 'event_date', 'status', 'organizer_id', 'min_price', 'currency'])
            ->with(['organizer:id,business_name', 'category:id,name']); // Optimized eager loading
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(5000),
                        Forms\Components\Select::make('organizer_id')
                            ->relationship('organizer', 'business_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $organizer = \App\Models\Organizer::find($state);
                                    if ($organizer) {
                                        $set('commission_rate', $organizer->commission_rate);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'paused' => 'Paused',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Commission Settings')
                    ->schema([
                        Forms\Components\Select::make('commission_type')
                            ->label('Commission Type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                            ])
                            ->default('percentage')
                            ->reactive()
                            ->required(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label(fn ($get) => $get('commission_type') === 'fixed' ? 'Commission Amount' : 'Commission Rate')
                            ->numeric()
                            ->suffix(fn ($get) => $get('commission_type') === 'fixed' ? 'KES' : '%')
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn ($get) => $get('commission_type') === 'fixed' ? null : 100)
                            ->step(0.1)
                            ->helperText(fn ($get, $record) => $get('organizer_id')
                                    ? 'Organizer default: '.\App\Models\Organizer::find($get('organizer_id'))?->commission_rate.'%'
                                    : 'Select organizer to see default rate'
                            ),
                        Forms\Components\Toggle::make('featured')
                            ->label('Featured Event')
                            ->helperText('Featured events appear at the top of search results')
                            ->inline(false),
                        Forms\Components\TextInput::make('featured_priority')
                            ->label('Featured Priority')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Higher numbers appear first (0-100)')
                            ->visible(fn ($get) => $get('featured')),
                    ])
                    ->columns(2)
                    ->description('Override organizer\'s default commission for this specific event'),

                Forms\Components\Section::make('Venue & Location')
                    ->schema([
                        Forms\Components\TextInput::make('venue_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('venue_address')
                            ->maxLength(500),
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Date & Pricing')
                    ->schema([
                        Forms\Components\DateTimePicker::make('event_date')
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_date'),
                        Forms\Components\TextInput::make('min_price')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->minValue(0),
                        Forms\Components\TextInput::make('max_price')
                            ->numeric()
                            ->prefix('KES')
                            ->minValue(0),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'KES' => 'KES - Kenyan Shilling',
                                'NGN' => 'NGN - Nigerian Naira',
                                'ZAR' => 'ZAR - South African Rand',
                                'GHS' => 'GHS - Ghanaian Cedi',
                                'UGX' => 'UGX - Ugandan Shilling',
                                'TZS' => 'TZS - Tanzanian Shilling',
                                'EGP' => 'EGP - Egyptian Pound',
                                'USD' => 'USD - US Dollar',
                            ])
                            ->default('KES')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('organizer.business_name')
                    ->label('Organizer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('venue_name')
                    ->label('Venue')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_price')
                    ->label('Price')
                    ->money(fn ($record) => strtolower($record->currency ?? 'kes'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Commission')
                    ->getStateUsing(fn ($record) => $record->commission_type === 'fixed'
                            ? 'KES '.number_format($record->commission_rate)
                            : $record->commission_rate.'%'
                    )
                    ->badge()
                    ->color(fn ($record) => $record->commission_rate <= 1.5 ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('featured')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'paused' => 'warning',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'paused' => 'Paused',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('organizer')
                    ->relationship('organizer', 'business_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleFeatured')
                    ->label(fn ($record) => $record->featured ? 'Unfeature' : 'Feature')
                    ->icon(fn ($record) => $record->featured ? 'heroicon-o-x-mark' : 'heroicon-o-star')
                    ->color(fn ($record) => $record->featured ? 'warning' : 'success')
                    ->action(function ($record) {
                        $record->update([
                            'featured' => ! $record->featured,
                            'featured_priority' => $record->featured ? 0 : 50,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->featured ? 'Event featured' : 'Event unfeatured')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('commissionOverride')
                    ->label('Commission')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('commission_type')
                            ->label('Commission Type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                            ])
                            ->default(fn ($record) => $record->commission_type)
                            ->reactive()
                            ->required(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label(fn ($get) => $get('commission_type') === 'fixed' ? 'Commission Amount' : 'Commission Rate')
                            ->numeric()
                            ->suffix(fn ($get) => $get('commission_type') === 'fixed' ? 'KES' : '%')
                            ->default(fn ($record) => $record->commission_rate)
                            ->required()
                            ->minValue(0)
                            ->maxValue(fn ($get) => $get('commission_type') === 'fixed' ? null : 100)
                            ->step(0.1),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update($data);
                        \Filament\Notifications\Notification::make()
                            ->title('Commission updated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
