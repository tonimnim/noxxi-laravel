<?php

namespace App\Filament\Admin\Resources\RefundRequestResource\Pages;

use App\Filament\Admin\Resources\RefundRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRefundRequests extends ListRecords
{
    protected static string $resource = RefundRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Export is handled via bulk actions in the table
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RefundRequestResource\Widgets\RefundOverviewStats::class,
        ];
    }
}
