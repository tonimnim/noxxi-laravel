<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use App\Services\CloudinaryService;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Log;

class MediaMarketingStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Media & Marketing')
            ->description('Add images and marketing details')
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\FileUpload::make('media')
                    ->label('Listing Images')
                    ->image()
                    ->multiple()
                    ->maxFiles(3)
                    ->maxSize(5120) // 5MB
                    ->disk('public')  // Use public disk for direct access
                    ->directory('event-images')
                    ->visibility('public')
                    ->downloadable()
                    ->reorderable()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'])
                    ->helperText('Upload up to 3 images (JPG, PNG, GIF, WebP - max 5MB each). First image will be the cover image.')
                    ->storeFileNamesIn('media_file_names')
                    ->getUploadedFileNameForStorageUsing(function ($file) {
                        // Generate unique filename with safer extension handling
                        $extension = $file->getClientOriginalExtension();
                        // Convert avif to jpg if needed
                        if (strtolower($extension) === 'avif') {
                            $extension = 'jpg';
                        }
                        return 'event_' . uniqid() . '.' . $extension;
                    })
                    ->columnSpanFull(),
            ]);
    }
}