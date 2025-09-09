<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use App\Models\EventCategory;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Illuminate\Support\Str;

class BasicInformationStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Basic Information')
            ->description('Tell us about your listing')
            ->icon('heroicon-o-information-circle')
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Listing Title')
                    ->placeholder('Enter a catchy title for your listing')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->helperText('Choose a clear, descriptive title that will attract attendees'),
                    
                Forms\Components\TextInput::make('slug')
                    ->label('URL Slug')
                    ->disabled()
                    ->dehydrated()
                    ->unique(ignoreRecord: true)
                    ->helperText('Auto-generated from title'),
                    
                Forms\Components\Select::make('listing_type')
                    ->label('Listing Type')
                    ->options([
                        'event' => 'Event (One-time or multi-day)',
                        'service' => 'Service (Ongoing, no specific date)',
                        'recurring' => 'Recurring Event (Weekly/Monthly)',
                    ])
                    ->default('event')
                    ->required()
                    ->reactive()
                    ->helperText('Choose the type of listing you want to create'),
                    
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->options(function () {
                        // Cache categories for 1 hour to improve performance
                        return cache()->remember('event_categories_children_only', 3600, function () {
                            $categories = EventCategory::whereNull('parent_id')
                                ->with('children')
                                ->orderBy('display_order')
                                ->get();
                            
                            $options = [];
                            
                            // Only show child categories, grouped by parent
                            foreach ($categories as $parent) {
                                if ($parent->children->count() > 0) {
                                    $group = [];
                                    foreach ($parent->children as $child) {
                                        $group[$child->id] = $child->name;
                                    }
                                    // Add children under a group with parent name
                                    $options[$parent->name] = $group;
                                }
                            }
                            
                            return $options;
                        });
                    })
                    ->required()
                    ->searchable()
                    ->preload() // Preload all options for faster interaction
                    ->optionsLimit(100) // Show all options without limit
                    ->helperText('Select a category for your listing'),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Description (Optional)')
                    ->placeholder('Describe your listing in detail...')
                    ->maxLength(5000)
                    ->rows(6)
                    ->helperText('Include key details about what attendees can expect')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}