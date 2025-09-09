<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;

class PoliciesTermsStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Policies & Terms')
            ->description('Set your terms and policies')
            ->icon('heroicon-o-document-text')
            ->schema([
                static::getPoliciesSection(),
                static::getCheckInSettingsSection(),
                static::getListingSettingsSection(),
            ]);
    }
    
    protected static function getPoliciesSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Policies')
            ->schema([
                Forms\Components\RichEditor::make('policies.terms_conditions')
                    ->label('Terms & Conditions (Optional)')
                    ->placeholder('Enter your terms and conditions including refund policy...')
                    ->maxLength(5000)
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                        'link',
                    ])
                    ->helperText('Include all terms, conditions, and refund policy')
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }
    
    protected static function getCheckInSettingsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Check-in Settings')
            ->description('Control when and how attendees can check in')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('check_in_enabled')
                            ->label('Enable Check-in')
                            ->default(true)
                            ->helperText('Allow ticket scanning and check-in')
                            ->reactive()
                            ->columnSpan(1),
                            
                        Forms\Components\Toggle::make('allow_immediate_check_in')
                            ->label('Allow Immediate Check-in')
                            ->default(true)
                            ->helperText('Allow check-in without time restrictions')
                            ->reactive()
                            ->columnSpan(1),
                    ]),
                    
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('check_in_opens_at')
                            ->label('Check-in Opens At')
                            ->helperText('When check-in window opens (leave empty for always open)')
                            ->displayFormat('Y-m-d H:i')
                            ->native(false)
                            ->visible(fn (Forms\Get $get) => !$get('allow_immediate_check_in'))
                            ->minDate(fn () => now())
                            ->beforeOrEqual('event_date')
                            ->requiredIf('allow_immediate_check_in', false)
                            ->columnSpan(1),
                            
                        Forms\Components\DateTimePicker::make('check_in_closes_at')
                            ->label('Check-in Closes At')
                            ->helperText('When check-in window closes (leave empty for never)')
                            ->displayFormat('Y-m-d H:i')
                            ->native(false)
                            ->visible(fn (Forms\Get $get) => !$get('allow_immediate_check_in'))
                            ->after('check_in_opens_at')
                            ->afterOrEqual('event_date')
                            ->columnSpan(1),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('check_in_enabled')),
            ])
            ->columns(1);
    }
    
    protected static function getListingSettingsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Listing Settings')
            ->schema([
                Forms\Components\Toggle::make('listing_settings.enable_reviews')
                    ->label('Enable Reviews')
                    ->default(true)
                    ->helperText('Allow attendees to leave reviews'),
            ])
            ->columns(1);
    }
}