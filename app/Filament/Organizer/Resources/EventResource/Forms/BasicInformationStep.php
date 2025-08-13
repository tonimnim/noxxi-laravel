<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use App\Models\EventCategory;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
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
                    
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->options(function () {
                        // Cache categories for 1 hour to improve performance
                        return cache()->remember('event_categories_grouped', 3600, function () {
                            $categories = EventCategory::whereNull('parent_id')
                                ->with('children')
                                ->orderBy('display_order')
                                ->get();
                            
                            $options = [];
                            foreach ($categories as $parent) {
                                // Group child categories by parent name
                                $group = [];
                                foreach ($parent->children as $child) {
                                    $group[$child->id] = $child->name;
                                }
                                if (!empty($group)) {
                                    $options[$parent->name] = $group;
                                }
                            }
                            return $options;
                        });
                    })
                    ->required()
                    ->searchable()
                    ->preload() // Preload all options for faster interaction
                    ->helperText('Select a category for your listing')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            // Cache individual category lookups
                            $category = cache()->remember("category_{$state}", 3600, function () use ($state) {
                                return EventCategory::with('parent')->find($state);
                            });
                            if ($category) {
                                $set('category_metadata', static::getCategoryDefaults($category));
                            }
                        }
                    }),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Describe your listing in detail...')
                    ->required()
                    ->maxLength(5000)
                    ->rows(6)
                    ->helperText('Include key details about what attendees can expect')
                    ->columnSpanFull(),
                    
                Forms\Components\TagsInput::make('tags')
                    ->placeholder('Add tags (press Enter after each)')
                    ->separator(',')
                    ->helperText('Add relevant tags to help people find your listing')
                    ->columnSpanFull(),
                    
                // Dynamic Category-Specific Fields
                static::getCategorySpecificFields(),
            ])
            ->columns(2);
    }
    
    protected static function getCategoryDefaults($category): array
    {
        $parentSlug = $category->parent ? $category->parent->slug : $category->slug;
        
        return match($parentSlug) {
            'cinema' => [
                'rating' => null,
                'language' => 'English',
                'subtitles' => false,
                'screen_type' => 'Standard',
            ],
            'sports' => [
                'teams' => null,
                'tournament' => null,
                'seating_sections' => [],
            ],
            'experiences' => [
                'duration' => null,
                'group_size_limit' => null,
                'skill_level' => 'All Levels',
            ],
            default => [],
        };
    }
    
    protected static function getCategorySpecificFields(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Category-Specific Details')
            ->description('Additional information based on your category')
            ->schema([
                // Cinema Fields
                Forms\Components\Select::make('category_metadata.rating')
                    ->label('Rating')
                    ->options([
                        'G' => 'G - General Audiences',
                        'PG' => 'PG - Parental Guidance',
                        'PG-13' => 'PG-13 - Parents Strongly Cautioned',
                        'R' => 'R - Restricted',
                        'NC-17' => 'NC-17 - Adults Only',
                    ])
                    ->visible(fn ($get) => static::isCinemaCategory($get('category_id')))
                    ->columnSpan(1),
                    
                Forms\Components\Select::make('category_metadata.language')
                    ->label('Language')
                    ->options([
                        'English' => 'English',
                        'French' => 'French',
                        'Arabic' => 'Arabic',
                        'Swahili' => 'Swahili',
                        'Yoruba' => 'Yoruba',
                        'Zulu' => 'Zulu',
                        'Amharic' => 'Amharic',
                        'Portuguese' => 'Portuguese',
                    ])
                    ->visible(fn ($get) => static::isCinemaCategory($get('category_id')))
                    ->columnSpan(1),
                    
                Forms\Components\Toggle::make('category_metadata.subtitles')
                    ->label('Has Subtitles')
                    ->visible(fn ($get) => static::isCinemaCategory($get('category_id')))
                    ->columnSpan(1),
                    
                Forms\Components\Select::make('category_metadata.screen_type')
                    ->label('Screen Type')
                    ->options([
                        'Standard' => 'Standard',
                        'IMAX' => 'IMAX',
                        '3D' => '3D',
                        '4DX' => '4DX',
                        'VIP' => 'VIP',
                    ])
                    ->visible(fn ($get) => static::isCinemaCategory($get('category_id')))
                    ->columnSpan(1),
                    
                // Sports Fields
                Forms\Components\TextInput::make('category_metadata.teams')
                    ->label('Teams/Participants')
                    ->placeholder('e.g., Team A vs Team B')
                    ->visible(fn ($get) => static::isSportsCategory($get('category_id')))
                    ->columnSpan(1),
                    
                Forms\Components\TextInput::make('category_metadata.tournament')
                    ->label('Tournament/League')
                    ->placeholder('e.g., Premier League, Champions Cup')
                    ->visible(fn ($get) => static::isSportsCategory($get('category_id')))
                    ->columnSpan(1),
                    
                Forms\Components\TagsInput::make('category_metadata.seating_sections')
                    ->label('Seating Sections')
                    ->placeholder('Add section names')
                    ->visible(fn ($get) => static::isSportsCategory($get('category_id')))
                    ->columnSpanFull(),
                    
                // Experiences Fields
                Forms\Components\TextInput::make('category_metadata.duration')
                    ->label('Duration')
                    ->placeholder('e.g., 2 hours, 3 days')
                    ->visible(fn ($get) => static::isExperiencesCategory($get('category_id')))
                    ->columnSpan(1),
                    
                Forms\Components\TextInput::make('category_metadata.group_size_limit')
                    ->label('Group Size Limit')
                    ->numeric()
                    ->placeholder('Maximum participants')
                    ->visible(fn ($get) => static::isExperiencesCategory($get('category_id')))
                    ->columnSpan(1),
                    
                Forms\Components\Select::make('category_metadata.skill_level')
                    ->label('Skill Level Required')
                    ->options([
                        'Beginner' => 'Beginner',
                        'Intermediate' => 'Intermediate',
                        'Advanced' => 'Advanced',
                        'All Levels' => 'All Levels',
                    ])
                    ->visible(fn ($get) => static::isExperiencesCategory($get('category_id')))
                    ->columnSpan(1),
            ])
            ->columns(2)
            ->collapsible()
            ->collapsed(false)
            ->columnSpanFull()
            ->visible(fn ($get) => $get('category_id') !== null);
    }
    
    protected static function isCinemaCategory($categoryId): bool
    {
        if (!$categoryId) return false;
        $category = EventCategory::find($categoryId);
        if (!$category) return false;
        
        $parentSlug = $category->parent ? $category->parent->slug : $category->slug;
        return $parentSlug === 'cinema';
    }
    
    protected static function isSportsCategory($categoryId): bool
    {
        if (!$categoryId) return false;
        $category = EventCategory::find($categoryId);
        if (!$category) return false;
        
        $parentSlug = $category->parent ? $category->parent->slug : $category->slug;
        return $parentSlug === 'sports';
    }
    
    protected static function isExperiencesCategory($categoryId): bool
    {
        if (!$categoryId) return false;
        $category = EventCategory::find($categoryId);
        if (!$category) return false;
        
        $parentSlug = $category->parent ? $category->parent->slug : $category->slug;
        return $parentSlug === 'experiences';
    }
}