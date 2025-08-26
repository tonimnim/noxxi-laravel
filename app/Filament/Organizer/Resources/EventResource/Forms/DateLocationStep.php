<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use App\Models\City;
use Filament\Forms;
use Filament\Forms\Components\Wizard;

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
                            ->label('Start Date & Time')
                            ->required()
                            ->native(false)
                            ->displayFormat('M d, Y g:i A')
                            ->minDate(now()->addHours(1))
                            ->seconds(false)
                            ->helperText('When does your listing start?'),

                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('End Date & Time (Optional)')
                            ->native(false)
                            ->displayFormat('M d, Y g:i A')
                            ->minDate(now()->addHours(2))
                            ->seconds(false)
                            ->afterOrEqual('event_date')
                            ->helperText('Leave blank for single-day listings'),
                    ]),

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
                                    ->options(function () {
                                        return City::active()
                                            ->orderBy('country')
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($city) {
                                                return [$city->id => $city->name.', '.$city->country];
                                            });
                                    })
                                    ->helperText('Select the closest major city to your venue')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $city = City::find($state);
                                            if ($city) {
                                                // Auto-fill city name for backward compatibility
                                                $set('city', $city->name);
                                                // Auto-fill coordinates if available
                                                if ($city->latitude) {
                                                    $set('latitude', $city->latitude);
                                                }
                                                if ($city->longitude) {
                                                    $set('longitude', $city->longitude);
                                                }
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('city')
                                    ->label('City Name (Auto-filled)')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Auto-filled from city selection'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->placeholder('Auto-filled or enter manually')
                                    ->helperText('For map display'),

                                Forms\Components\TextInput::make('longitude')
                                    ->numeric()
                                    ->placeholder('Auto-filled or enter manually')
                                    ->helperText('For map display'),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Additional Settings')
                    ->schema([
                        Forms\Components\TextInput::make('capacity')
                            ->label('Total Capacity')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(100)
                            ->helperText('Maximum number of tickets available'),

                        Forms\Components\Select::make('age_restriction')
                            ->label('Age Restriction')
                            ->options([
                                0 => 'All Ages',
                                13 => '13+ (Teens and above)',
                                16 => '16+ (Young adults and above)',
                                18 => '18+ (Adults only)',
                                21 => '21+ (Legal drinking age)',
                            ])
                            ->default(0)
                            ->helperText('Select the minimum age requirement'),
                    ])
                    ->columns(2),
            ]);
    }
}
