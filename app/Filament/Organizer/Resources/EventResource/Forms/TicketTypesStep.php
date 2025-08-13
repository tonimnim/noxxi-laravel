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
        return Forms\Components\Select::make('currency')
            ->label('Currency')
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
            ->default(fn () => Auth::user()->organizer?->default_currency ?? 'KES')
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
                    
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->placeholder('What does this ticket include?')
                    ->rows(2)
                    ->maxLength(500),
                    
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('sale_start')
                            ->label('Sale Start Date')
                            ->native(false)
                            ->displayFormat('M d, Y g:i A')
                            ->default(now())
                            ->helperText('When tickets go on sale'),
                            
                        Forms\Components\DateTimePicker::make('sale_end')
                            ->label('Sale End Date')
                            ->native(false)
                            ->displayFormat('M d, Y g:i A')
                            ->helperText('When ticket sales close'),
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
            ->itemLabel(fn (array $state): ?string => 
                $state['name'] ?? 'New Ticket Type'
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
                    
                Forms\Components\Toggle::make('ticket_sales_config.enable_waiting_list')
                    ->label('Enable Waiting List')
                    ->default(false)
                    ->helperText('Allow registration when sold out'),
            ])
            ->columns(3);
    }
}