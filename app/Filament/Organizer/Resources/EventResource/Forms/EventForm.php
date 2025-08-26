<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use App\Models\EventCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;

class EventForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                static::basicInformationSection(),
                static::eventDetailsSection(),
                static::venueInformationSection(),
                static::pricingSection(),
            ]);
    }

    protected static function basicInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Basic Information')
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Str::slug($state))
                    ),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(5000)
                    ->rows(5),
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->options(function () {
                        // Use cached categories
                        return cache()->remember('event_categories_grouped', 3600, function () {
                            $categories = EventCategory::whereNull('parent_id')
                                ->with('children')
                                ->orderBy('display_order')
                                ->get();

                            $options = [];
                            foreach ($categories as $parent) {
                                if ($parent->children->isNotEmpty()) {
                                    // Parent category has subcategories - create grouped options
                                    $group = [];
                                    foreach ($parent->children as $child) {
                                        $group[$child->id] = $child->name;
                                    }
                                    $options[$parent->name] = $group;
                                } else {
                                    // Parent category has no subcategories - add as direct option
                                    $options[$parent->id] = $parent->name;
                                }
                            }

                            return $options;
                        });
                    })
                    ->required()
                    ->searchable()
                    ->preload(),
            ])->columns(2);
    }

    protected static function eventDetailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Event Details')
            ->schema([
                Forms\Components\DateTimePicker::make('event_date')
                    ->label('Start Date & Time')
                    ->required()
                    ->native(false)
                    ->displayFormat('M d, Y g:i A')
                    ->minDate(now()),
                Forms\Components\DateTimePicker::make('end_date')
                    ->label('End Date & Time')
                    ->native(false)
                    ->displayFormat('M d, Y g:i A')
                    ->after('event_date'),
                Forms\Components\TextInput::make('capacity')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(100),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Live',
                        'paused' => 'Paused',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required(),
            ])->columns(2);
    }

    protected static function venueInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Venue Information')
            ->schema([
                Forms\Components\TextInput::make('venue_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('venue_address')
                    ->required()
                    ->maxLength(500),
                Forms\Components\TextInput::make('city')
                    ->required()
                    ->maxLength(100),
            ])->columns(2);
    }

    protected static function pricingSection(): Forms\Components\Section
    {
        // Get currencies from config
        $currencies = config('currencies.supported', []);
        $popular = config('currencies.popular', []);

        // Reorganize with popular currencies first
        $options = [];
        foreach ($popular as $code) {
            if (isset($currencies[$code])) {
                $options[$code] = $currencies[$code];
            }
        }

        // Add separator
        if (! empty($options) && count($currencies) > count($options)) {
            $options['---'] = '──────────────────────';
        }

        // Add remaining currencies
        foreach ($currencies as $code => $name) {
            if (! isset($options[$code])) {
                $options[$code] = $name;
            }
        }

        return Forms\Components\Section::make('Pricing')
            ->schema([
                Forms\Components\Select::make('currency')
                    ->options($options)
                    ->disableOptionWhen(fn (string $value): bool => $value === '---')
                    ->default(fn () => Auth::user()->organizer?->default_currency ?? config('currencies.default', 'USD'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('min_price')
                    ->numeric()
                    ->prefix(fn ($get) => $get('currency') ?? config('currencies.default', 'USD'))
                    ->required()
                    ->minValue(0),
                Forms\Components\TextInput::make('max_price')
                    ->numeric()
                    ->prefix(fn ($get) => $get('currency') ?? config('currencies.default', 'USD'))
                    ->required()
                    ->gte('min_price'),
            ])->columns(3);
    }
}
