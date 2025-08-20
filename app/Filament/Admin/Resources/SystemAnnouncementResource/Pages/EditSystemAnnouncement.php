<?php

namespace App\Filament\Admin\Resources\SystemAnnouncementResource\Pages;

use App\Filament\Admin\Resources\SystemAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditSystemAnnouncement extends EditRecord
{
    protected static string $resource = SystemAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    // Clear cache after deletion
                    Cache::forget('admin.system.announcements.list');
                    Cache::forget('admin.system.critical_alerts');
                }),
        ];
    }
    
    protected function afterSave(): void
    {
        // Clear cache to reflect changes immediately
        Cache::forget('admin.system.announcements.list');
        Cache::forget('admin.system.critical_alerts');
    }
}
