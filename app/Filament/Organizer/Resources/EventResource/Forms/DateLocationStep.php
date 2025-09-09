<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use App\Models\City;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;

class DateLocationStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Date & Location')
            ->description('When and where is your listing')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('event_date')
                            ->label(fn (Get $get) => $get('listing_type') === 'service' ? 'Service Available From (Optional)' : 'Start Date & Time')
                            ->required(fn (Get $get) => $get('listing_type') !== 'service')
                            ->native(false)
                            ->displayFormat('M d, Y g:i A')
                            ->minDate(now()->addHours(1))
                            ->seconds(false)
                            ->visible(fn (Get $get) => $get('listing_type') !== 'service' || $get('end_date'))
                            ->helperText(fn (Get $get) => $get('listing_type') === 'service' ? 'Optional: When service becomes available' : 'When does your listing start?'),

                        Forms\Components\DateTimePicker::make('end_date')
                            ->label(fn (Get $get) => $get('listing_type') === 'service' ? 'Service Available Until (Optional)' : 'End Date & Time (Optional)')
                            ->native(false)
                            ->displayFormat('M d, Y g:i A')
                            ->minDate(now()->addHours(2))
                            ->seconds(false)
                            ->afterOrEqual('event_date')
                            ->helperText(fn (Get $get) => $get('listing_type') === 'service' ? 'Optional: When service stops being available' : 'Leave blank for single-day listings'),
                    ])
                    ->visible(fn (Get $get) => $get('listing_type') !== 'service')
                    ->columnSpanFull(),
                    
                Forms\Components\Placeholder::make('service_note')
                    ->label('')
                    ->content('Services are ongoing and don\'t require specific dates. Customers can book anytime.')
                    ->visible(fn (Get $get) => $get('listing_type') === 'service'),

                Forms\Components\Section::make('Venue Details')
                    ->description('Where will your listing take place?')
                    ->schema([
                        Forms\Components\TextInput::make('venue_name')
                            ->label('Venue Name')
                            ->placeholder('e.g., Nairobi National Theatre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('venue_address')
                            ->label('Full Address')
                            ->placeholder('Enter the complete address')
                            ->required()
                            ->rows(2)
                            ->maxLength(500),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('city_id')
                                    ->label('City')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->optionsLimit(250) // Show all cities
                                    ->options(function () {
                                        return City::active()
                                            ->orderBy('country')
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($city) {
                                                return [$city->id => $city->name.', '.$city->country];
                                            });
                                    })
                                    ->getSearchResultsUsing(function (string $search) {
                                        return City::active()
                                            ->where(function ($query) use ($search) {
                                                $query->where('name', 'ILIKE', "%{$search}%")
                                                    ->orWhere('country', 'ILIKE', "%{$search}%");
                                            })
                                            ->orderBy('country')
                                            ->orderBy('name')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($city) {
                                                return [$city->id => $city->name.', '.$city->country];
                                            });
                                    })
                                    ->helperText('Type to search for your city or scroll to see all')
                                    ->reactive(),

                                Forms\Components\TextInput::make('city')
                                    ->label('City Name')
                                    ->required()
                                    ->placeholder('Enter city name')
                                    ->maxLength(255)
                                    ->helperText('Enter the city name for your venue'),
                            ]),

                        Forms\Components\TextInput::make('capacity')
                            ->label('Total Capacity')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(100)
                            ->helperText('Maximum number of tickets available'),
                    ])
                    ->columns(1),
            ]);
    }
}
