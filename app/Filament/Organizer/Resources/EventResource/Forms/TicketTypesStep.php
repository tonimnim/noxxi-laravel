<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Auth;

class TicketTypesStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Ticket Types')
            ->description('Configure your ticket types and pricing')
            ->icon('heroicon-o-ticket')
            ->schema([
                static::getCurrencyField(),
                static::getTicketTypesRepeater(),
                static::getSalesConfigSection(),
            ]);
    }

    protected static function getCurrencyField(): Forms\Components\Select
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

        return Forms\Components\Select::make('currency')
            ->label('Currency')
            ->options($options)
            ->disableOptionWhen(fn (string $value): bool => $value === '---')
            ->default(fn () => Auth::user()->organizer?->default_currency ?? config('currencies.default', 'USD'))
            ->required()
            ->searchable()
            ->reactive()
            ->helperText('Select the currency for all ticket prices');
    }

    protected static function getTicketTypesRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('ticket_types')
            ->label('Ticket Types')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Type Name')
                            ->placeholder('e.g., Early Bird, VIP, Regular')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix(fn ($get) => $get('../../currency') ?? 'KES'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity Available')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(100),
                    ]),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('max_per_order')
                            ->label('Max Per Order')
                            ->numeric()
                            ->minValue(1)
                            ->default(10)
                            ->helperText('Limit per transaction'),

                        Forms\Components\Toggle::make('transferable')
                            ->label('Transferable')
                            ->default(true)
                            ->helperText('Can be transferred'),

                        Forms\Components\Toggle::make('refundable')
                            ->label('Refundable')
                            ->default(false)
                            ->helperText('Allow refunds'),
                    ]),
            ])
            ->defaultItems(1)
            ->addActionLabel('+ Add Ticket Type')
            ->reorderable()
            ->collapsible()
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Ticket Type'
            )
            ->columnSpanFull()
            ->required()
            ->minItems(1)
            ->maxItems(10)
            ->helperText('You must create at least one ticket type. You can add up to 10 different types.');
    }

    protected static function getSalesConfigSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Sales Configuration')
            ->description('Configure ticket sales settings')
            ->schema([
                Forms\Components\TextInput::make('ticket_sales_config.max_tickets_per_order')
                    ->label('Max Tickets Per Order')
                    ->numeric()
                    ->minValue(1)
                    ->default(10)
                    ->helperText('Maximum tickets in single purchase'),

                Forms\Components\Toggle::make('ticket_sales_config.show_remaining_tickets')
                    ->label('Show Remaining Tickets')
                    ->default(true)
                    ->helperText('Display available ticket count to buyers'),
            ])
            ->columns(3);
    }
}
