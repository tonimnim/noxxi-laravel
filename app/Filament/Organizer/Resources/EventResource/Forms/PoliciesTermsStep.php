<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use Filament\Forms;
use Filament\Forms\Components\Wizard;

class PoliciesTermsStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Policies & Terms')
            ->description('Set your terms and policies')
            ->icon('heroicon-o-document-text')
            ->schema([
                static::getPoliciesSection(),
                static::getListingSettingsSection(),
            ]);
    }
    
    protected static function getPoliciesSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Policies')
            ->schema([
                Forms\Components\RichEditor::make('policies.terms_conditions')
                    ->label('Terms & Conditions')
                    ->placeholder('Enter your terms and conditions...')
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
                    ->helperText('Specific terms attendees must agree to')
                    ->columnSpanFull(),
                    
                Forms\Components\RichEditor::make('policies.refund_policy')
                    ->label('Refund Policy')
                    ->placeholder('Describe your refund policy...')
                    ->maxLength(2000)
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'bulletList',
                        'orderedList',
                        'link',
                    ])
                    ->helperText('When and how refunds are processed')
                    ->columnSpanFull(),
                    
                Forms\Components\Textarea::make('policies.what_included')
                    ->label("What's Included")
                    ->placeholder('List what attendees will receive...')
                    ->rows(2)
                    ->maxLength(1000)
                    ->helperText('Benefits and inclusions'),
                    
                Forms\Components\Textarea::make('policies.what_not_included')
                    ->label("What's Not Included")
                    ->placeholder('List exclusions...')
                    ->rows(2)
                    ->maxLength(1000)
                    ->helperText('Items not covered by ticket'),
                    
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('policies.dress_code')
                            ->label('Dress Code')
                            ->placeholder('e.g., Smart Casual, Formal')
                            ->maxLength(100),
                            
                        Forms\Components\Textarea::make('policies.special_instructions')
                            ->label('Special Instructions')
                            ->placeholder('Any special instructions...')
                            ->rows(2)
                            ->maxLength(500),
                    ]),
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