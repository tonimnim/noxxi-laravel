{{-- Noxxi Filament Panel Guidelines --}}

# NOXXI FILAMENT DEVELOPMENT GUIDELINES

## PANEL STRUCTURE

### Three Separate Panels
1. **Organizer Panel** (`/organizer`) - Event management
2. **Admin Panel** (`/admin`) - System administration  
3. **User Panel** (`/user`) - Ticket management

### Panel Configuration
```php
// app/Providers/Filament/OrganizerPanelProvider.php
->id('organizer')
->path('organizer')
->colors([
    'primary' => Color::Amber,
])
->sidebarWidth('15rem')
->pages([
    \App\Filament\Organizer\Pages\Dashboard::class, // Custom dashboard
])
```

## DASHBOARD CUSTOMIZATION

### Remove Dashboard Title
```php
// app/Filament/Organizer/Pages/Dashboard.php
public function getHeading(): string|Htmlable
{
    return '';
}

public function getSubheading(): ?string
{
    return null;
}
```

### Custom Header Actions
```php
// In PanelProvider
->renderHook(
    'panels::user-menu.before',
    fn () => view('filament.organizer.partials.header-icons')
)
```

## RESOURCE PATTERNS

### Multi-Step Wizard for Complex Forms
```php
// app/Filament/Organizer/Resources/EventResource/Pages/CreateEvent.php
use HasWizard;

protected function getSteps(): array
{
    return [
        Forms\BasicInformationStep::make(),
        Forms\DateLocationStep::make(),
        Forms\TicketTypesStep::make(),
        Forms\MediaMarketingStep::make(),
        Forms\PoliciesTermsStep::make(),
        Forms\ReviewPublishStep::make(),
    ];
}
```

### Split Large Files
Keep files under 400 lines by splitting into:
```
EventResource/
├── Forms/
│   ├── BasicInformationStep.php
│   ├── DateLocationStep.php
│   └── TicketTypesStep.php
├── Tables/
│   └── EventTable.php
└── Pages/
    ├── ListEvents.php
    ├── CreateEvent.php
    └── EditEvent.php
```

## FORM COMPONENTS

### Dynamic Category-Specific Fields
```php
Forms\Components\Select::make('category_id')
    ->reactive()
    ->afterStateUpdated(function ($state, callable $set) {
        // Show/hide fields based on category
    }),

// Cinema-specific fields
Forms\Components\Select::make('rating')
    ->visible(fn ($get) => static::isCinemaCategory($get('category_id'))),
```

### JSONB Field Handling
```php
Forms\Components\Repeater::make('ticket_types')
    ->schema([
        Forms\Components\TextInput::make('name'),
        Forms\Components\TextInput::make('price')
            ->numeric()
            ->prefix(fn ($get) => $get('../../currency') ?? 'KES'),
    ])
    ->defaultItems(1)
    ->maxItems(10),
```

### Rich Text Editor Configuration
```php
Forms\Components\RichEditor::make('terms_conditions')
    ->toolbarButtons([
        'bold',
        'italic', 
        'bulletList',
        'orderedList',
        'link',
    ])
    ->maxLength(5000),
```

## TABLE CONFIGURATION

### Optimized Table Setup
```php
return $table
    ->columns(static::getColumns())
    ->filters(static::getFilters())
    ->actions(static::getActions())
    ->defaultSort('created_at', 'desc')
    ->paginated([10, 25, 50])
    ->striped()
    ->deferLoading() // Important for performance
    ->recordClasses(fn () => 'hover:bg-gray-50');
```

### Custom Column Formatting
```php
Tables\Columns\TextColumn::make('title')
    ->description(fn (Event $record): string => 
        $record->venue_name . ' • ' . $record->city
    )
    ->wrap()
    ->searchable()
    ->sortable(),

Tables\Columns\TextColumn::make('status')
    ->badge()
    ->color(fn ($state, $record): string => 
        $record->isSoldOut() ? 'danger' : match($state) {
            'published' => 'success',
            'draft' => 'gray',
            'paused' => 'warning',
            default => 'gray',
        }
    ),
```

### Bulk Actions
```php
Tables\Actions\BulkAction::make('export')
    ->action(function ($records) {
        // Export logic
    })
    ->requiresConfirmation(),
```

## WIDGETS

### Stats Widget Pattern
```php
class OrganizerStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Revenue', $this->formatCurrency($revenue))
                ->description('This month')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),
        ];
    }
    
    protected function formatCurrency($amount): string
    {
        $currency = auth()->user()->organizer->default_currency ?? 'KES';
        return $currency . ' ' . number_format($amount, 0);
    }
}
```

### Table Widget with Actions
```php
class BookingsTable extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->where('organizer_id', auth()->user()->organizer->id)
                    ->latest()
            )
            ->columns([...])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
```

## CACHING STRATEGIES

### Cache Expensive Queries
```php
// Cache categories for dropdown
Forms\Components\Select::make('category_id')
    ->options(function () {
        return cache()->remember('event_categories_grouped', 3600, function () {
            // Expensive query here
        });
    })
    ->preload(), // Load all options at once
```

### Clear Cache After Updates
```php
protected function afterSave(): void
{
    cache()->forget('event_categories_grouped');
    cache()->forget('dashboard_stats_' . auth()->id());
}
```

## AUTHORIZATION

### Resource Authorization
```php
// In Resource class
public static function canViewAny(): bool
{
    return auth()->user()->hasRole('organizer');
}

public static function canEdit(Model $record): bool
{
    return $record->organizer_id === auth()->user()->organizer->id;
}
```

### Page-Level Authorization
```php
public function mount(): void
{
    abort_unless(auth()->user()->can('manage-events'), 403);
}
```

## NAVIGATION

### Custom Navigation Items
```php
// In PanelProvider
->navigationItems([
    NavigationItem::make('Analytics')
        ->url(fn (): string => route('filament.organizer.pages.analytics'))
        ->icon('heroicon-o-chart-bar')
        ->sort(2),
])
```

### Conditional Navigation
```php
->navigationGroups([
    NavigationGroup::make('Events')
        ->items([
            NavigationItem::make('All Events')
                ->visible(fn (): bool => auth()->user()->can('view-all-events')),
        ]),
])
```

## RESPONSIVE DESIGN

### Mobile-Friendly Tables
```php
->contentGrid([
    'md' => 1,  // 1 column on medium screens
    'xl' => 1,  // 1 column on extra large
])
->toggleableColumns() // Allow hiding columns on mobile
```

### Responsive Form Layouts
```php
Forms\Components\Section::make()
    ->schema([...])
    ->columns([
        'sm' => 1,
        'md' => 2,
        'lg' => 3,
    ]),
```

## PERFORMANCE OPTIMIZATION

### Defer Loading
```php
// In tables
->deferLoading() // Don't load until user interacts

// In selects
->searchable() // Load options via search
->optionsLimit(50) // Limit initial options
```

### Eager Loading
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['organizer', 'category', 'bookings']);
}
```

### Select Specific Columns
```php
->query(
    Event::select([
        'id', 'title', 'event_date', 'status', 'min_price'
    ])
)
```

## CUSTOM PAGES

### Analytics Dashboard
```php
// app/Filament/Organizer/Pages/Analytics.php
class Analytics extends Page
{
    protected static string $view = 'filament.organizer.pages.analytics';
    
    public function getViewData(): array
    {
        return [
            'revenue' => $this->getRevenueData(),
            'bookings' => $this->getBookingsData(),
        ];
    }
}
```

### Custom Actions
```php
protected function getHeaderActions(): array
{
    return [
        Actions\Action::make('export')
            ->action(fn () => $this->export())
            ->icon('heroicon-o-arrow-down-tray'),
    ];
}
```

## FILE UPLOADS

### Image Upload Configuration
```php
Forms\Components\FileUpload::make('images')
    ->multiple()
    ->image()
    ->maxSize(5120) // 5MB
    ->directory('event-images')
    ->visibility('public')
    ->imageResizeMode('cover')
    ->imageCropAspectRatio('16:9')
    ->maxFiles(5),
```

## NOTIFICATIONS

### Success Messages
```php
Notification::make()
    ->title('Event created successfully')
    ->success()
    ->send();
```

### Error Handling
```php
try {
    // Operation
} catch (\Exception $e) {
    Notification::make()
        ->title('Operation failed')
        ->body($e->getMessage())
        ->danger()
        ->send();
}
```

## COMMON PATTERNS

### Status Management
```php
Tables\Actions\Action::make('toggle_status')
    ->action(function (Event $record): void {
        $record->update([
            'status' => $record->status === 'published' ? 'paused' : 'published'
        ]);
    })
    ->icon(fn (Event $record): string => 
        $record->status === 'published' ? 'heroicon-o-pause' : 'heroicon-o-play'
    ),
```

### Duplicate Functionality
```php
Tables\Actions\Action::make('duplicate')
    ->action(function (Event $record): void {
        $newEvent = $record->replicate();
        $newEvent->title = $record->title . ' (Copy)';
        $newEvent->status = 'draft';
        $newEvent->save();
    }),
```

## TESTING FILAMENT

### Test Resource Access
```php
public function test_organizer_can_access_events_resource()
{
    $organizer = User::factory()->organizer()->create();
    
    $this->actingAs($organizer)
        ->get(EventResource::getUrl('index'))
        ->assertSuccessful();
}
```

Remember: Keep Filament resources modular, use caching for performance, and always validate user permissions.