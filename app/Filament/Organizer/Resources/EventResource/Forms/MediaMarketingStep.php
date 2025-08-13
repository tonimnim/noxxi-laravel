<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use Filament\Forms;
use Filament\Forms\Components\Wizard;

class MediaMarketingStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Media & Marketing')
            ->description('Add images and marketing details')
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\FileUpload::make('media.images')
                    ->label('Listing Images')
                    ->image()
                    ->imageEditor()
                    ->multiple()
                    ->maxFiles(3)
                    ->maxSize(5120)
                    ->directory('listings/images')
                    ->visibility('public')
                    ->downloadable()
                    ->reorderable()
                    ->helperText('Upload up to 3 images (max 5MB each). First image will be the cover image.')
                    ->columnSpanFull(),
                    
                Forms\Components\TextInput::make('media.video_url')
                    ->label('Promotional Video URL (Optional)')
                    ->url()
                    ->placeholder('https://youtube.com/watch?v=...')
                    ->helperText('YouTube or Vimeo link')
                    ->columnSpanFull(),
                    
                Forms\Components\Section::make('SEO & Marketing')
                    ->description('Improve discoverability')
                    ->schema([
                        Forms\Components\TextInput::make('marketing.seo_title')
                            ->label('SEO Title')
                            ->placeholder('Page title for search engines')
                            ->maxLength(60)
                            ->helperText('60 characters max. Leave empty to use listing title'),
                            
                        Forms\Components\Textarea::make('marketing.seo_description')
                            ->label('Meta Description')
                            ->placeholder('Brief description for search engines')
                            ->maxLength(160)
                            ->rows(2)
                            ->helperText('160 characters max for search results'),
                            
                        Forms\Components\TagsInput::make('seo_keywords')
                            ->label('SEO Keywords')
                            ->placeholder('Add keywords (press Enter)')
                            ->separator(',')
                            ->helperText('Keywords to improve search ranking'),
                            
                        Forms\Components\Toggle::make('marketing.featured')
                            ->label('Feature this listing')
                            ->helperText('Request to feature on homepage (may require approval)'),
                    ])
                    ->columns(1),
            ]);
    }
}