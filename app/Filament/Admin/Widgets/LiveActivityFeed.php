<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ActivityLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;

class LiveActivityFeed extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
    ];
    
    protected static bool $isLazy = true;
    
    protected static ?string $pollingInterval = null; // Manual refresh for now
    
    public ?string $filter = 'important'; // Default to important activities
    
    protected function getTableHeading(): string|Htmlable|null
    {
        $lastUpdate = Cache::get('admin.activity_feed.last_update', now());
        $timeAgo = now()->diffForHumans($lastUpdate, true);
        
        return new HtmlString(
            '<div class="flex items-center justify-between">' .
            '<span>Live Activity Feed</span>' .
            '<div class="flex items-center gap-2">' .
            '<span class="text-xs text-gray-500">Updated ' . $timeAgo . ' ago</span>' .
            '<button onclick="window.location.reload()" class="text-gray-400 hover:text-gray-600 transition-colors" title="Refresh">' .
            '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">' .
            '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />' .
            '</svg>' .
            '</button>' .
            '</div>' .
            '</div>'
        );
    }
    
    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('level')
                ->options([
                    'all' => 'All Activities',
                    'important' => 'Important Only',
                    'critical' => 'Critical Only',
                ])
                ->default('important')
                ->query(function (Builder $query, array $data): Builder {
                    return match($data['value'] ?? 'important') {
                        'important' => $query->whereIn('level', [ActivityLog::LEVEL_CRITICAL, ActivityLog::LEVEL_IMPORTANT]),
                        'critical' => $query->where('level', ActivityLog::LEVEL_CRITICAL),
                        default => $query,
                    };
                }),
            
            Tables\Filters\SelectFilter::make('type')
                ->options([
                    ActivityLog::TYPE_PAYMENT => 'Payments',
                    ActivityLog::TYPE_ORGANIZER => 'Organizers',
                    ActivityLog::TYPE_EVENT => 'Events',
                    ActivityLog::TYPE_USER => 'Users',
                    ActivityLog::TYPE_SYSTEM => 'System',
                ])
                ->multiple()
                ->placeholder('All Types'),
        ];
    }
    
    public function table(Table $table): Table
    {
        Cache::put('admin.activity_feed.last_update', now(), 300);
        
        return $table
            ->query(
                ActivityLog::query()
                    ->with(['subject', 'causer'])
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
            )
            ->columns([
                Tables\Columns\IconColumn::make('icon_name')
                    ->label('')
                    ->color(fn ($record): string => $record->icon_color)
                    ->size('lg'),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('Activity')
                    ->description(fn ($record): ?string => $record->description)
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('formatted_time')
                    ->label('Time')
                    ->size('sm')
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ActivityLog::LEVEL_CRITICAL => 'danger',
                        ActivityLog::LEVEL_IMPORTANT => 'warning',
                        ActivityLog::LEVEL_INFO => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters($this->getTableFilters())
            ->filtersFormColumns(2)
            ->paginated([10])
            ->striped(false)
            ->poll(null)
            ->emptyStateHeading('No activities yet')
            ->emptyStateDescription('Activities will appear here as they happen')
            ->emptyStateIcon('heroicon-o-clock');
    }
}