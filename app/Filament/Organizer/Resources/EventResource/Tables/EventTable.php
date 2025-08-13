<?php

namespace App\Filament\Organizer\Resources\EventResource\Tables;

use App\Models\Event;
use App\Models\EventCategory;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getColumns())
            ->filters(static::getFilters(), layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions(static::getActions())
            ->bulkActions(static::getBulkActions())
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Search listings...')
            ->paginated([10, 25, 50])
            ->striped()
            ->deferLoading()
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->recordClasses(fn () => 'hover:bg-gray-50 dark:hover:bg-gray-800/50');
    }
    
    protected static function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')
                ->label('Listing')
                ->searchable()
                ->sortable()
                ->description(fn (Event $record): string => 
                    $record->venue_name . ' • ' . $record->city . "\n" .
                    'LST-' . strtoupper(substr($record->id, 0, 4))
                )
                ->wrap()
                ->extraAttributes(['class' => 'min-w-[200px]']),
                
            Tables\Columns\TextColumn::make('category.name')
                ->label('Category')
                ->badge()
                ->color(fn ($state, $record): string => static::getCategoryColor($record))
                ->sortable()
                ->toggleable()
                ->toggledHiddenByDefault(false),
                
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn ($state, Event $record): string => static::getStatusColor($state, $record))
                ->formatStateUsing(fn (string $state, Event $record): string => static::formatStatus($state, $record))
                ->sortable(),
                
            Tables\Columns\TextColumn::make('min_price')
                ->label('From')
                ->formatStateUsing(fn (Event $record): string => 
                    $record->currency . ' ' . number_format($record->min_price, 0)
                )
                ->sortable()
                ->toggleable()
                ->toggledHiddenByDefault(false),
                
            Tables\Columns\TextColumn::make('tickets_sold')
                ->label('Sold')
                ->formatStateUsing(fn (Event $record): string => 
                    $record->tickets_sold . '/' . $record->capacity
                )
                ->sortable()
                ->alignCenter()
                ->toggleable(),
                
            Tables\Columns\TextColumn::make('total_revenue')
                ->label('Revenue')
                ->formatStateUsing(fn (Event $record): string => 
                    $record->currency . ' ' . number_format($record->total_revenue, 0)
                )
                ->sortable()
                ->toggleable(),
                
            Tables\Columns\TextColumn::make('event_date')
                ->label('Date(s)')
                ->formatStateUsing(function (Event $record): string {
                    if ($record->end_date && !$record->event_date->isSameDay($record->end_date)) {
                        return $record->event_date->format('M d') . ' - ' . 
                               $record->end_date->format('M d, Y');
                    }
                    return $record->event_date->format('M d, Y');
                })
                ->sortable()
                ->toggleable(),
        ];
    }
    
    protected static function getFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status_filter')
                ->options([
                    'all' => 'All statuses',
                    'published' => 'Live',
                    'sold_out' => 'Sold Out',
                    'draft' => 'Draft',
                    'paused' => 'Paused',
                ])
                ->default('all')
                ->query(function (Builder $query, array $data): Builder {
                    if ($data['value'] === 'sold_out') {
                        return $query->where('status', 'published')
                                    ->whereColumn('tickets_sold', '>=', 'capacity');
                    } elseif ($data['value'] !== 'all' && $data['value']) {
                        return $query->where('status', $data['value']);
                    }
                    return $query;
                })
                ->label('Status')
                ->placeholder(false),
                
            Tables\Filters\SelectFilter::make('category_id')
                ->label('Category')
                ->placeholder('All categories')
                ->options(function () {
                    // Use cached categories
                    return cache()->remember('event_categories_filter', 3600, function () {
                        $categories = EventCategory::whereNull('parent_id')
                            ->with('children')
                            ->orderBy('display_order')
                            ->get();
                        
                        $options = [];
                        foreach ($categories as $parent) {
                            // Only add child categories, grouped by parent
                            foreach ($parent->children as $child) {
                                $options[$parent->name][$child->id] = $child->name;
                            }
                        }
                        return $options;
                    });
                }),
        ];
    }
    
    protected static function getActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-m-document-duplicate')
                    ->label('Duplicate')
                    ->requiresConfirmation()
                    ->action(function (Event $record): void {
                        $newEvent = $record->replicate();
                        $newEvent->title = $record->title . ' (Copy)';
                        $newEvent->slug = \Str::slug($newEvent->title);
                        $newEvent->status = 'draft';
                        $newEvent->tickets_sold = 0;
                        $newEvent->save();
                    }),
            ])->iconButton()
            ->icon('heroicon-m-ellipsis-vertical')
            ->tooltip('Actions'),
        ];
    }
    
    protected static function getBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('export')
                ->icon('heroicon-o-arrow-down-tray')
                ->label('Export Selected')
                ->action(function ($records) {
                    // Export logic here
                }),
            Tables\Actions\DeleteBulkAction::make(),
        ];
    }
    
    protected static function getCategoryColor($record): string
    {
        $parentSlug = $record->category->parent_id 
            ? $record->category->parent->slug 
            : $record->category->slug;
            
        return match($parentSlug) {
            'events' => 'info',
            'sports' => 'success',
            'cinema' => 'danger',
            'experiences' => 'warning',
            default => 'gray',
        };
    }
    
    protected static function getStatusColor(string $state, Event $record): string
    {
        return $record->isSoldOut() ? 'danger' : match ($state) {
            'published' => 'success',
            'draft' => 'gray',
            'paused' => 'warning',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
    
    protected static function formatStatus(string $state, Event $record): string
    {
        return $record->isSoldOut() ? 'Sold Out' : match ($state) {
            'published' => 'Live',
            'draft' => 'Draft',
            'paused' => 'Paused',
            'cancelled' => 'Cancelled',
            default => ucfirst($state),
        };
    }
}