{{-- Noxxi Platform Project Guidelines --}}

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