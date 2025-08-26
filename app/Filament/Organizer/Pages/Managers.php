<?php

namespace App\Filament\Organizer\Pages;

use App\Models\Event;
use App\Models\OrganizerManager;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Managers extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false; // Hide from navigation

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Managers';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.organizer.pages.managers';

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addManager')
                ->label('Add Manager')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add New Manager')
                ->modalDescription('Search for a user by email or phone number to add them as a manager')
                ->modalWidth('lg')
                ->form([
                    TextInput::make('search')
                        ->label('Email or Phone Number')
                        ->placeholder('Enter email or phone number')
                        ->required()
                        ->helperText('Search for an existing user by their email or phone number'),

                    Select::make('event_access')
                        ->label('Event Access')
                        ->options([
                            'all' => 'All Events',
                            'specific' => 'Specific Events',
                        ])
                        ->default('all')
                        ->reactive()
                        ->required(),

                    Select::make('event_ids')
                        ->label('Select Events')
                        ->multiple()
                        ->options(function () {
                            return Cache::remember(
                                'organizer_events_'.Auth::user()->organizer->id,
                                300,
                                fn () => Event::where('organizer_id', Auth::user()->organizer->id)
                                    ->where('status', '!=', 'cancelled')
                                    ->orderBy('event_date', 'desc')
                                    ->pluck('title', 'id')
                            );
                        })
                        ->visible(fn ($get) => $get('event_access') === 'specific')
                        ->searchable()
                        ->required(fn ($get) => $get('event_access') === 'specific'),
                ])
                ->action(function (array $data): void {
                    // Search for user by email or phone
                    $search = trim($data['search']);
                    $user = User::where('email', $search)
                        ->orWhere('phone_number', $search)
                        ->first();

                    if (! $user) {
                        Notification::make()
                            ->title('User not found')
                            ->body('No user found with that email or phone number. Please ensure they have registered on the platform.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Check if already a manager
                    $existingManager = OrganizerManager::where('organizer_id', Auth::user()->organizer->id)
                        ->where('user_id', $user->id)
                        ->where('is_active', true)
                        ->first();

                    if ($existingManager) {
                        Notification::make()
                            ->title('Already a manager')
                            ->body('This user is already a manager for your organization.')
                            ->warning()
                            ->send();

                        return;
                    }

                    // Create new manager
                    OrganizerManager::create([
                        'organizer_id' => Auth::user()->organizer->id,
                        'user_id' => $user->id,
                        'granted_by' => Auth::id(),
                        'can_scan_tickets' => true,
                        'can_validate_entries' => true,
                        'event_ids' => $data['event_access'] === 'specific' ? $data['event_ids'] : null,
                        'is_active' => true,
                    ]);

                    Notification::make()
                        ->title('Manager added successfully')
                        ->body($user->full_name.' can now scan tickets for your events.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrganizerManager::query()
                    ->where('organizer_id', Auth::user()->organizer->id)
                    ->with(['user', 'grantedBy'])
            )
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('user.phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event_access')
                    ->label('Access Level')
                    ->formatStateUsing(function ($record) {
                        if (empty($record->event_ids)) {
                            return 'All Events';
                        }
                        $count = count($record->event_ids);

                        return $count.' '.($count === 1 ? 'Event' : 'Events');
                    })
                    ->badge()
                    ->color(fn ($record) => empty($record->event_ids) ? 'success' : 'info'),

                TextColumn::make('last_scan')
                    ->label('Last Activity')
                    ->getStateUsing(function ($record) {
                        $lastScan = Ticket::where('used_by', $record->user_id)
                            ->whereHas('event', function ($q) use ($record) {
                                $q->where('organizer_id', $record->organizer_id);
                            })
                            ->orderBy('used_at', 'desc')
                            ->first();

                        return $lastScan ? $lastScan->used_at->diffForHumans() : 'No activity';
                    }),

                TextColumn::make('total_scans')
                    ->label('Total Scans')
                    ->getStateUsing(function ($record) {
                        return Ticket::where('used_by', $record->user_id)
                            ->whereHas('event', function ($q) use ($record) {
                                $q->where('organizer_id', $record->organizer_id);
                            })
                            ->count();
                    })
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ])
                    ->default(true),
            ])
            ->actions([
                TableAction::make('viewEvents')
                    ->label('View Events')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Manager Event Access')
                    ->modalContent(function ($record) {
                        if (empty($record->event_ids)) {
                            return view('filament.organizer.components.manager-events', [
                                'events' => ['All Events'],
                                'hasAllAccess' => true,
                            ]);
                        }

                        $events = Event::whereIn('id', $record->event_ids)
                            ->pluck('title')
                            ->toArray();

                        return view('filament.organizer.components.manager-events', [
                            'events' => $events,
                            'hasAllAccess' => false,
                        ]);
                    })
                    ->visible(fn ($record) => $record->is_active),

                TableAction::make('toggle')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => ! $record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? 'Manager activated' : 'Manager deactivated')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove Manager')
                    ->modalDescription('Are you sure you want to remove this manager? They will no longer be able to scan tickets.')
                    ->successNotificationTitle('Manager removed'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No managers yet')
            ->emptyStateDescription('Add team members who can help scan tickets at your events.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    /**
     * Get scan activity for display in a separate section
     */
    public function getScanActivity(): array
    {
        $organizerId = Auth::user()->organizer->id;

        return Cache::remember("scan_activity_{$organizerId}", 60, function () use ($organizerId) {
            return DB::table('tickets')
                ->join('users', 'tickets.used_by', '=', 'users.id')
                ->join('events', 'tickets.event_id', '=', 'events.id')
                ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
                ->join('users as customers', 'bookings.user_id', '=', 'customers.id')
                ->where('events.organizer_id', $organizerId)
                ->whereNotNull('tickets.used_at')
                ->select([
                    'users.full_name as scanner_name',
                    'events.title as event_title',
                    'customers.full_name as customer_name',
                    'tickets.used_at',
                    'tickets.ticket_type',
                ])
                ->orderBy('tickets.used_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($scan) {
                    return [
                        'scanner' => $scan->scanner_name,
                        'event' => $scan->event_title,
                        'customer' => $scan->customer_name,
                        'ticket_type' => $scan->ticket_type,
                        'scanned_at' => \Carbon\Carbon::parse($scan->used_at)->diffForHumans(),
                    ];
                })
                ->toArray();
        });
    }
}
