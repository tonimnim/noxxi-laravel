<laravel-boost-guidelines>
=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== filament/core rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


### Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== fluxui-free/core rules ===

## Flux UI Free

- This project is using the free edition of Flux UI. It has full access to the free components and variants, but does not have access to the Pro components.
- Flux UI is a component library for Livewire. Flux is a robust, hand-crafted, UI component library for your Livewire applications. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.
- You should use Flux UI components when available.
- Fallback to standard Blade components if Flux is unavailable.
- If available, use Laravel Boost's `search-docs` tool to get the exact documentation and code snippets available for this project.
- Flux UI components look like this:
<code-snippet name="Flux UI Component Usage Example" lang="blade">
    <button type="button" class="relative items-center font-medium justify-center gap-2 whitespace-nowrap disabled:opacity-75 dark:disabled:opacity-75 disabled:cursor-default disabled:pointer-events-none h-10 text-sm rounded-lg w-10 inline-flex  bg-[var(--color-accent)] hover:bg-[color-mix(in_oklab,_var(--color-accent),_transparent_10%)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0 shadow-[inset_0px_1px_--theme(--color-white/.2)] [[data-flux-button-group]_&amp;]:border-e-0 [:is([data-flux-button-group]&gt;&amp;:last-child,_[data-flux-button-group]_:last-child&gt;&amp;)]:border-e-[1px] dark:[:is([data-flux-button-group]&gt;&amp;:last-child,_[data-flux-button-group]_:last-child&gt;&amp;)]:border-e-0 dark:[:is([data-flux-button-group]&gt;&amp;:last-child,_[data-flux-button-group]_:last-child&gt;&amp;)]:border-s-[1px] [:is([data-flux-button-group]&gt;&amp;:not(:first-child),_[data-flux-button-group]_:not(:first-child)&gt;&amp;)]:border-s-[color-mix(in_srgb,var(--color-accent-foreground),transparent_85%)]" data-flux-button="data-flux-button" data-flux-group-target="data-flux-group-target">
        
    </button>
</code-snippet>

### Available Components
This is correct as of Boost installation, but there may be additional components within the codebase.

<available-flux-components>
avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, profile, radio, select, separator, switch, text, textarea, tooltip
</available-flux-components>


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()`) for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff"
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== .ai/noxxi-api rules ===

# NOXXI API DEVELOPMENT GUIDELINES

## API ARCHITECTURE

### Base URL Structure
- Development: `http://localhost:8000/api`
- Production: `https://api.noxxi.com/api`
- Version prefix: `/v1` for versioned endpoints

### Authentication
- **Method**: Laravel Passport OAuth2
- **Token Type**: Bearer
- **Header Format**: `Authorization: Bearer {access_token}`
- **Token Expiry**: 24 hours
- **Refresh Strategy**: Not implemented (login again)

## RESPONSE STANDARDS

### Success Response Structure
```php
return $this->success(
    data: $data,
    message: 'Operation successful',
    code: 200
);
```

### Error Response Structure
```php
return $this->error(
    message: 'Error description',
    code: 400,
    errors: ['field' => ['error message']]
);
```

### Pagination Response
```php
return $this->success([
    'items' => $paginated->items(),
    'meta' => [
        'current_page' => $paginated->currentPage(),
        'last_page' => $paginated->lastPage(),
        'per_page' => $paginated->perPage(),
        'total' => $paginated->total(),
    ]
]);
```

## ENDPOINT PATTERNS

### RESTful Conventions
```
GET    /api/resources          - List all (paginated)
GET    /api/resources/{id}     - Get single resource
POST   /api/resources          - Create new resource
PUT    /api/resources/{id}     - Update entire resource
PATCH  /api/resources/{id}     - Partial update
DELETE /api/resources/{id}     - Delete resource
```

### Action Endpoints
```
POST   /api/resources/{id}/publish    - Publish action
POST   /api/resources/{id}/cancel     - Cancel action
GET    /api/resources/{id}/related    - Get related data
```

## QUERY PARAMETERS

### Using Spatie QueryBuilder
```php
public function index(Request $request)
{
    $items = QueryBuilder::for(Model::class)
        ->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::partial('name'),
            AllowedFilter::scope('active'),
            AllowedFilter::callback('custom', fn($q, $v) => $q->where(...))
        ])
        ->allowedIncludes(['relation1', 'relation2'])
        ->allowedSorts(['created_at', 'name', 'price'])
        ->defaultSort('-created_at')
        ->paginate($request->per_page ?? 20);
        
    return $this->success([...]);
}
```

### Filter Examples
```
GET /api/events?filter[city]=Nairobi
GET /api/events?filter[price_min]=1000&filter[price_max]=5000
GET /api/events?filter[date_after]=2025-01-01
GET /api/events?sort=-event_date,title
GET /api/events?include=organizer,category
GET /api/events?page=2&per_page=20
```

## VALIDATION PATTERNS

### Form Request Usage
```php
class CreateEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'event_date' => 'required|date|after:today',
            'category_id' => 'required|uuid|exists:event_categories,id',
            'min_price' => 'required|numeric|min:0',
            'currency' => 'required|in:KES,NGN,ZAR,GHS,UGX,TZS,EGP,USD',
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'Event title is required',
            'event_date.after' => 'Event date must be in the future',
        ];
    }
}
```

### Validation Error Response
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "title": ["The title field is required."],
        "price": ["The price must be a number."]
    }
}
```

## MOBILE APP SPECIFIC ENDPOINTS

### Ticket Endpoints
```php
// Get tickets with QR data
GET /api/tickets/{id}
Response includes:
- ticket details
- qr_data (base64 encoded)
- event information
- venue details

// Download manifest for offline
GET /api/v1/events/{id}/manifest
Response includes:
- all valid tickets
- signatures for validation
- event details
- last_updated timestamp
```

### Payment Flow
```php
// Initialize payment
POST /api/payments/{method}/initialize
{
    "booking_id": "uuid",
    "phone_number": "+254..." // For M-Pesa
}

// Verify payment
GET /api/payments/verify/{transaction_id}
Returns payment status and booking confirmation
```

## ERROR HANDLING

### HTTP Status Codes
```php
200 OK              - Successful GET/PUT
201 Created         - Successful POST
204 No Content      - Successful DELETE
400 Bad Request     - Invalid request
401 Unauthorized    - Missing/invalid token
403 Forbidden       - No permission
404 Not Found       - Resource doesn't exist
422 Unprocessable   - Validation errors
429 Too Many        - Rate limit exceeded
500 Server Error    - Internal error
```

### Error Response Examples
```php
// Not found
return $this->notFound('Event not found');

// Unauthorized
return $this->unauthorized('Invalid credentials');

// Forbidden
return $this->forbidden('You cannot access this resource');

// Validation
return $this->validationError($validator->errors());
```

## SECURITY REQUIREMENTS

### Input Validation
- Always validate UUIDs
- Sanitize search queries
- Validate file uploads
- Check array sizes
- Validate date ranges

### Authorization Checks
```php
// In controllers
if (!$user->can('update', $event)) {
    return $this->forbidden();
}

// Using policies
$this->authorize('update', $event);

// Using middleware
Route::middleware(['auth:api', 'can:manage-events'])
```

### Rate Limiting
```php
// In RouteServiceProvider
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Custom limits
RateLimiter::for('search', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});
```

## PERFORMANCE OPTIMIZATION

### Query Optimization
```php
// Use select to limit fields
Event::select(['id', 'title', 'event_date', 'min_price'])
    ->with(['organizer:id,business_name'])
    ->paginate();

// Use eager loading
$events = Event::with(['organizer', 'category'])->get();

// Cache frequent queries
$categories = cache()->remember('categories', 3600, function () {
    return EventCategory::all();
});
```

### Response Optimization
```php
// Return only needed fields
return $this->success([
    'id' => $event->id,
    'title' => $event->title,
    'date' => $event->event_date->format('Y-m-d'),
    'price' => $event->min_price,
]);

// Use API Resources for complex transformations
return new EventResource($event);
```

## TESTING API ENDPOINTS

### Test Structure
```php
public function test_can_list_events()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user, 'api')
        ->getJson('/api/events');
        
    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'data' => [
                'events' => [
                    '*' => ['id', 'title', 'event_date']
                ],
                'meta' => ['current_page', 'total']
            ]
        ]);
}
```

### Common Test Scenarios
- Authentication required
- Validation errors
- Successful operations
- Permission checks
- Rate limiting
- Edge cases

## API DOCUMENTATION

### Using Scribe
```php
/**
 * Get event details
 * 
 * @group Events
 * @authenticated
 * 
 * @urlParam id uuid required The event ID. Example: 123e4567-e89b-12d3-a456-426614174000
 * 
 * @response 200 {
 *   "status": "success",
 *   "data": {
 *     "id": "123e4567-e89b-12d3-a456-426614174000",
 *     "title": "Jazz Night",
 *     "event_date": "2025-02-15"
 *   }
 * }
 */
public function show($id)
{
    // Implementation
}
```

## WEBHOOK HANDLING

### Payment Webhooks
```php
// Verify webhook signature
$signature = $request->header('X-Webhook-Signature');
if (!$this->verifyWebhookSignature($payload, $signature)) {
    return response('Invalid signature', 401);
}

// Process webhook
DB::transaction(function () use ($payload) {
    // Update payment status
    // Create tickets if successful
    // Send notifications
});

// Always return 200 to acknowledge receipt
return response('OK', 200);
```

## COMMON API PATTERNS

### Search Implementation
```php
public function search(Request $request)
{
    $query = Event::query();
    
    if ($request->q) {
        $query->where(function ($q) use ($request) {
            $q->where('title', 'ILIKE', "%{$request->q}%")
              ->orWhere('description', 'ILIKE', "%{$request->q}%");
        });
    }
    
    return $this->success($query->paginate());
}
```

### Bulk Operations
```php
public function bulkDelete(Request $request)
{
    $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'uuid|exists:events,id'
    ]);
    
    Event::whereIn('id', $request->ids)
        ->where('organizer_id', auth()->user()->organizer->id)
        ->delete();
        
    return $this->success(null, 'Events deleted successfully');
}
```

### File Uploads
```php
public function uploadImage(Request $request)
{
    $request->validate([
        'image' => 'required|image|max:5120' // 5MB
    ]);
    
    $path = $request->file('image')->store('event-images', 'public');
    
    return $this->success([
        'url' => Storage::url($path)
    ]);
}
```

Remember: APIs should be stateless, cacheable, and follow REST principles. Always consider mobile app constraints like bandwidth and offline capability.


=== .ai/noxxi-filament rules ===

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


=== .ai/noxxi-project rules ===

# NOXXI PLATFORM OVERVIEW

Noxxi is an African-focused event ticketing and management platform supporting multiple countries and currencies across Africa. The platform connects event organizers with attendees through a comprehensive listing, booking, and ticket management system.

## CRITICAL PROJECT RULES

### DEVELOPMENT PHILOSOPHY
- **THINK → READ → THINK → EXECUTE**: Always understand requirements, read existing code, reconsider approach, then implement
- **No over-engineering**: Keep solutions simple and straightforward
- **Consistency first**: Follow existing patterns in the codebase
- **Context awareness**: Always understand surrounding code before making changes
- **User-centric naming**: Use "Listings" instead of "Events" for consistency

### TECHNOLOGY STACK
- **Backend**: Laravel 12.x with PostgreSQL (Docker)
- **Admin Panels**: Filament 3.x (separate panels for organizers, users, admins)
- **Frontend**: Vue.js for user-facing pages
- **Authentication**: Laravel Passport OAuth2
- **API**: REST API for mobile app consumption (Flutter)
- **Payments**: Paystack, M-Pesa integration (backend handles all processing)

## DATABASE ARCHITECTURE

### Key Models & Relationships
- **Users** → can be attendees or have organizer accounts
- **Organizers** → belong to users, create listings
- **Events/Listings** → belong to organizers, have multiple ticket types
- **Bookings** → belong to users, reference events
- **Tickets** → belong to bookings, contain QR codes
- **Categories** → hierarchical (parent/child), 4 main: Events, Sports, Cinema, Experiences

### UUID Primary Keys
All models use UUIDs as primary keys. Always use HasUuids trait and set:
```php
public $incrementing = false;
protected $keyType = 'string';
```

### JSONB Fields
PostgreSQL JSONB fields store complex data like ticket_types, policies, media, etc. Always cast as 'array' in models.

## MULTI-CURRENCY SUPPORT

The platform supports African currencies:
- KES (Kenyan Shilling)
- NGN (Nigerian Naira)
- ZAR (South African Rand)
- GHS (Ghanaian Cedi)
- UGX (Ugandan Shilling)
- TZS (Tanzanian Shilling)
- EGP (Egyptian Pound)
- USD (US Dollar)

Always format prices with currency symbol and use user's default currency.

## FILAMENT PANEL STRUCTURE

### Organizer Panel (/organizer)
- Custom dashboard without title (override getHeading())
- Sidebar width: 15rem
- Resources: EventResource (for listings)
- Widgets: Stats, Revenue, Bookings, Activity Feed
- Header actions: Export, Create Listing, Notifications, Settings

### Admin Panel (/admin)
- Full system management
- User/Organizer management
- Transaction oversight
- System settings

### User Panel (/user)
- Ticket management
- Booking history
- Profile settings

## API STRUCTURE

### Standardized Responses
All API responses use ApiResponse trait:
```php
return $this->success($data, 'Message', 200);
return $this->error('Error message', 400, $errors);
```

### Response Format
```json
{
  "status": "success|error",
  "message": "Description",
  "data": {},
  "errors": {}
}
```

### Authentication
- Use Laravel Passport OAuth2
- Include Bearer token in headers
- Protected routes use auth:api middleware

## QR CODE & TICKET SYSTEM

### QR Code Structure
- Contains ticket ID, event ID, booking ID
- HMAC-SHA256 signature for security
- Base64 encoded
- Supports offline validation with manifests

### Ticket Validation Flow
1. Scanner reads QR code
2. Validates signature
3. Checks ticket status
4. Records check-in
5. Returns validation result

## NAMING CONVENTIONS

### Database
- Tables: plural, snake_case (events, bookings, tickets)
- Columns: snake_case (event_date, min_price)
- Foreign keys: model_id (organizer_id, user_id)
- JSONB fields: descriptive names (ticket_types, category_metadata)

### Models & Controllers
- Models: Singular PascalCase (Event, Booking)
- Controllers: PascalCase with Controller suffix (EventController)
- API Controllers: In Api namespace (Api\EventController)
- Filament Resources: ModelResource (EventResource)

### Routes
- API routes: kebab-case, RESTful (/api/events, /api/bookings/{id})
- Web routes: kebab-case (/login, /register-organizer)

## CODE PATTERNS

### Service Layer Pattern
Complex business logic goes in service classes:
```php
app/Services/
├── PaymentService.php
├── QrCodeService.php
├── TicketService.php
└── NotificationService.php
```

### Repository Pattern
Not currently used - direct Eloquent usage is preferred for simplicity.

### Query Builder Usage
Use Spatie QueryBuilder for API filtering:
```php
QueryBuilder::for(Event::class)
    ->allowedFilters(['city', 'category_id'])
    ->allowedSorts(['event_date', 'price'])
    ->paginate();
```

## TESTING APPROACH

### Test Data
- Test organizer: anto@gmail.com / 8800kl
- Test OTP: 123456 (development only)
- Mock payment responses for development

### Testing Commands
```bash
php artisan test
php artisan test --filter=EventTest
```

## COMMON TASKS

### Creating a New Listing Feature
1. Create migration with UUID and JSONB fields
2. Create model with proper casts
3. Create Filament resource with multi-step wizard
4. Add API endpoints with filtering
5. Implement service layer for complex logic

### Adding Payment Gateway
1. Create service in app/Services/
2. Implement initialize and verify methods
3. Add webhook handler
4. Update PaymentController
5. Never store sensitive keys in code

### Implementing New Widget
1. Create in app/Filament/Organizer/Widgets/
2. Use Filament table or stats components
3. Cache expensive queries
4. Make responsive with proper grid

## SECURITY REQUIREMENTS

### Never Do
- Store API keys/secrets in code
- Log sensitive information
- Trust user input without validation
- Use raw SQL queries
- Expose internal IDs in URLs

### Always Do
- Validate all inputs
- Use policies for authorization
- Hash sensitive data
- Use HTTPS in production
- Implement rate limiting

## PERFORMANCE OPTIMIZATION

### Caching Strategy
- Cache categories for 1 hour
- Cache expensive dashboard queries
- Use Redis when available
- Clear cache after updates

### Database Optimization
- Use eager loading to prevent N+1
- Index foreign keys and search fields
- Use select() to limit columns
- Paginate large result sets

## MOBILE APP INTEGRATION

### API Design Principles
- Keep endpoints RESTful
- Use consistent naming
- Return minimal data
- Support offline mode where possible
- Version APIs when breaking changes needed

### Flutter App Structure
- Feature-first architecture
- Services call Laravel APIs only
- No direct database access
- OAuth2 token management
- Offline ticket validation support

## ERROR HANDLING

### API Errors
- Return proper HTTP status codes
- Include error details in response
- Log errors for debugging
- Don't expose sensitive information

### Validation
- Use Form Requests for complex validation
- Return 422 with validation errors
- Provide clear error messages
- Validate at multiple layers

## DEPLOYMENT CONSIDERATIONS

### Environment Variables
- Never commit .env file
- Use proper environment configs
- Different keys for dev/staging/prod
- Secure storage for production secrets

### Database Migrations
- Always reversible
- Test rollbacks
- Use transactions for data migrations
- Never drop columns in production without backup

## AFRICAN MARKET FOCUS

### Localization
- Support multiple currencies
- Consider mobile-first users
- Optimize for low bandwidth
- Support offline functionality
- Use local payment methods

### Cultural Considerations
- Date/time formats per region
- Language support (future)
- Local payment preferences
- Mobile money integration priority

## COMMON PITFALLS TO AVOID

1. **Forgetting UUID setup in models**
2. **Not casting JSONB fields as arrays**
3. **Using wrong dashboard class in provider**
4. **Hardcoding currency symbols**
5. **Not caching expensive queries**
6. **Forgetting to run migrations after pulling**
7. **Not using the ApiResponse trait**
8. **Creating files instead of editing existing ones**
9. **Adding emojis to code**
10. **Over-engineering simple features**

## WHEN IMPLEMENTING NEW FEATURES

Always ask:
1. Does this follow existing patterns?
2. Is this the simplest solution?
3. Will this work offline?
4. Is this mobile-friendly?
5. Does this support multi-currency?
6. Is this secure?
7. Will this scale?
8. Is the code testable?

Remember: The platform serves the African market with a focus on reliability, offline capability, and mobile-first design.
</laravel-boost-guidelines>