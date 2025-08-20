<?php

namespace App\Filament\Admin\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Models\Organizer;
use App\Models\Event;
use App\Services\ActivityService;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;

class PendingActions extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'md' => 2,
        'lg' => 1,
        'xl' => 1,
    ];
    
    protected static bool $isLazy = true;
    
    protected static ?string $pollingInterval = null; // Disable auto-polling
    
    protected function getTableHeading(): string|Htmlable|null
    {
        $count = Cache::remember('admin.pending_actions.count', 120, function () {
            $organizers = DB::table('organizers')->where('is_verified', false)->count();
            $events = DB::table('events')
                ->where('status', 'draft')
                ->where('requires_approval', true)
                ->count();
            return $organizers + $events;
        });
        
        if ($count > 0) {
            return new HtmlString(
                '<div class="flex items-center gap-2">' .
                '<span>Pending Actions</span>' .
                '<span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-orange-100 bg-orange-600 rounded-full">' . $count . '</span>' .
                '</div>'
            );
        }
        
        return 'Pending Actions';
    }
    
    public function getTableRecordKey($record): string
    {
        return (string) ($record->composite_id ?? uniqid());
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getPendingItemsQuery())
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Organizer Verification' => 'warning',
                        'Listing Approval' => 'info',
                        'Payout Ready' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Organizer Verification' => 'heroicon-o-building-office',
                        'Listing Approval' => 'heroicon-o-calendar',
                        'Payout Ready' => 'heroicon-o-banknotes',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record): string => $record->description),
                    
                Tables\Columns\TextColumn::make('time_ago')
                    ->label('Time')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('created_at', $direction);
                    }),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        'Low' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->button()
                    ->size('xs')
                    ->color('success')
                    ->icon('heroicon-m-check')
                    ->visible(fn ($record): bool => in_array($record->type, ['Organizer Verification', 'Listing Approval']))
                    ->action(function ($record) {
                        $this->handleApproval($record);
                    })
                    ->requiresConfirmation(),
                    
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->button()
                    ->size('xs')
                    ->color('danger')
                    ->icon('heroicon-m-x-mark')
                    ->visible(fn ($record): bool => in_array($record->type, ['Organizer Verification', 'Listing Approval']))
                    ->action(function ($record) {
                        $this->handleRejection($record);
                    })
                    ->requiresConfirmation(),
                    
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->button()
                    ->size('xs')
                    ->color('gray')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => $this->getViewUrl($record)),
            ])
            ->paginated([10])
            ->defaultSort('created_at', 'asc')
            ->emptyStateHeading('No pending actions')
            ->emptyStateDescription('All caught up! No items require your attention.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
    
    protected function getPendingItemsQuery(): Builder
    {
        Cache::put('admin.pending_actions.last_update', now(), 120);
        
        // Use a direct DB query to avoid Filament's model expectations
        $organizerQuery = DB::table('organizers')
            ->select([
                DB::raw("CONCAT('org_', id) as composite_id"),
                'id',
                DB::raw("'Organizer Verification' as type"),
                DB::raw("CONCAT(business_name, ' (', COALESCE(business_type, 'Unknown'), ')') as description"),
                'created_at',
                DB::raw("
                    CASE 
                        WHEN created_at > NOW() - INTERVAL '1 hour' THEN CONCAT(EXTRACT(MINUTE FROM NOW() - created_at)::INTEGER, ' min ago')
                        WHEN created_at > NOW() - INTERVAL '1 day' THEN CONCAT(EXTRACT(HOUR FROM NOW() - created_at)::INTEGER, ' hours ago')
                        WHEN created_at > NOW() - INTERVAL '7 days' THEN CONCAT(EXTRACT(DAY FROM NOW() - created_at)::INTEGER, ' days ago')
                        ELSE TO_CHAR(created_at, 'Mon DD')
                    END as time_ago
                "),
                DB::raw("
                    CASE 
                        WHEN created_at < NOW() - INTERVAL '3 days' THEN 'High'
                        WHEN created_at < NOW() - INTERVAL '1 day' THEN 'Medium'
                        ELSE 'Low'
                    END as priority
                "),
                DB::raw("'organizer' as entity_type")
            ])
            ->where('is_verified', false)
            ->where('is_active', true)
            ->limit(5);
            
        $eventsQuery = DB::table('events')
            ->join('organizers', 'events.organizer_id', '=', 'organizers.id')
            ->select([
                DB::raw("CONCAT('evt_', events.id) as composite_id"),
                'events.id',
                DB::raw("'Listing Approval' as type"),
                DB::raw("CONCAT(events.title, ' by ', organizers.business_name) as description"),
                'events.created_at',
                DB::raw("
                    CASE 
                        WHEN events.created_at > NOW() - INTERVAL '1 hour' THEN CONCAT(EXTRACT(MINUTE FROM NOW() - events.created_at)::INTEGER, ' min ago')
                        WHEN events.created_at > NOW() - INTERVAL '1 day' THEN CONCAT(EXTRACT(HOUR FROM NOW() - events.created_at)::INTEGER, ' hours ago')
                        WHEN events.created_at > NOW() - INTERVAL '7 days' THEN CONCAT(EXTRACT(DAY FROM NOW() - events.created_at)::INTEGER, ' days ago')
                        ELSE TO_CHAR(events.created_at, 'Mon DD')
                    END as time_ago
                "),
                DB::raw("
                    CASE 
                        WHEN events.event_date < NOW() + INTERVAL '7 days' THEN 'High'
                        WHEN events.event_date < NOW() + INTERVAL '14 days' THEN 'Medium'
                        ELSE 'Low'
                    END as priority
                "),
                DB::raw("'event' as entity_type")
            ])
            ->where('events.status', 'draft')
            ->where('events.requires_approval', true)
            ->limit(5);
        
        // Create a model instance without UUID expectations
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'pending_items';
            public $incrementing = false;
            protected $keyType = 'string';
            protected $primaryKey = 'composite_id';
            
            // Disable timestamps
            public $timestamps = false;
            
            // Prevent Filament from trying to query by ID
            public function getKeyName()
            {
                return 'composite_id';
            }
        };
        
        return $model::query()
            ->fromSub($organizerQuery->unionAll($eventsQuery), 'pending_items');
    }
    
    protected function handleApproval($record): void
    {
        DB::beginTransaction();
        
        try {
            // Extract the real ID from composite_id
            $realId = str_replace(['org_', 'evt_'], '', $record->composite_id);
            
            if ($record->entity_type === 'organizer') {
                // Update organizer verification status
                DB::table('organizers')
                    ->where('id', $realId)
                    ->update([
                        'is_verified' => true,
                        'verification_status' => 'verified',
                        'verified_at' => now(),
                        'updated_at' => now(),
                    ]);
                
                // Get organizer for activity logging
                $organizer = Organizer::find($realId);
                if ($organizer) {
                    // Log the approval activity
                    ActivityService::logOrganizer(
                        'approved',
                        $organizer,
                        'Organizer verified: ' . $organizer->business_name
                    );
                    
                    // Send notification to organizer (future implementation)
                    // TODO: Send email notification to organizer
                }
                
            } elseif ($record->entity_type === 'event') {
                // Update listing status
                DB::table('events')
                    ->where('id', $realId)
                    ->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'first_published_at' => DB::raw('COALESCE(first_published_at, NOW())'),
                        'requires_approval' => false,
                        'updated_at' => now(),
                    ]);
                
                // Get event for activity logging
                $event = Event::find($realId);
                if ($event) {
                    // Log the approval activity
                    ActivityService::logEvent(
                        'approved',
                        $event,
                        'Listing approved: ' . $event->title
                    );
                }
            }
            
            DB::commit();
            
            // Clear caches
            Cache::forget('admin.pending_actions.count');
            Cache::forget('admin.pending_actions.last_update');
            
            Notification::make()
                ->title('Approved successfully')
                ->body($record->entity_type === 'organizer' ? 'Organizer has been verified' : 'Listing has been published')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Approval failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function handleRejection($record): void
    {
        DB::beginTransaction();
        
        try {
            // Extract the real ID from composite_id
            $realId = str_replace(['org_', 'evt_'], '', $record->composite_id);
            
            if ($record->entity_type === 'organizer') {
                // Update organizer rejection status
                DB::table('organizers')
                    ->where('id', $realId)
                    ->update([
                        'is_verified' => false,
                        'verification_status' => 'rejected',
                        'updated_at' => now(),
                    ]);
                
                // Get organizer for activity logging
                $organizer = Organizer::find($realId);
                if ($organizer) {
                    // Log the rejection activity
                    ActivityService::logOrganizer(
                        'rejected',
                        $organizer,
                        'Organizer rejected: ' . $organizer->business_name
                    );
                    
                    // TODO: Send rejection email with reason
                }
                
            } elseif ($record->entity_type === 'event') {
                // Update listing status
                DB::table('events')
                    ->where('id', $realId)
                    ->update([
                        'status' => 'rejected',
                        'requires_approval' => true,
                        'updated_at' => now(),
                    ]);
                
                // Get event for activity logging
                $event = Event::find($realId);
                if ($event) {
                    // Log the rejection activity
                    ActivityService::logEvent(
                        'rejected',
                        $event,
                        'Listing rejected: ' . $event->title
                    );
                }
            }
            
            DB::commit();
            
            // Clear caches
            Cache::forget('admin.pending_actions.count');
            Cache::forget('admin.pending_actions.last_update');
            
            Notification::make()
                ->title('Rejected successfully')
                ->body($record->entity_type === 'organizer' ? 'Organizer has been rejected' : 'Listing has been rejected')
                ->warning()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Rejection failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getViewUrl($record): string
    {
        // Extract the real ID from composite_id
        $realId = str_replace(['org_', 'evt_'], '', $record->composite_id);
        
        if ($record->entity_type === 'organizer') {
            return route('filament.admin.resources.organizers.edit', ['record' => $realId]);
        } elseif ($record->entity_type === 'event') {
            return route('filament.admin.resources.events.edit', ['record' => $realId]);
        }
        
        return '#';
    }
}