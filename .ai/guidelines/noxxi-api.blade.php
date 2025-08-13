{{-- Noxxi API Development Guidelines --}}

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