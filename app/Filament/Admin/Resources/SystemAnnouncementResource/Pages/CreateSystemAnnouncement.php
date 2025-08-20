<?php

namespace App\Filament\Admin\Resources\SystemAnnouncementResource\Pages;

use App\Filament\Admin\Resources\SystemAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateSystemAnnouncement extends CreateRecord
{
    protected static string $resource = SystemAnnouncementResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Clear cache to show new announcement immediately
        Cache::forget('admin.system.announcements.list');
        Cache::forget('admin.system.critical_alerts');
    }
}
