# Noxxi Platform API Documentation

## Overview
The Noxxi Platform provides a comprehensive REST API for mobile app consumption, supporting both user and organizer functionalities across African markets.

## Current Implementation Status

### ✅ Installed Packages
- **Laravel Passport** - OAuth2 authentication (INSTALLED & CONFIGURED)
- **Spatie Laravel Query Builder** - Advanced filtering/sorting (INSTALLED & USED)
- **Spatie Laravel Data** - DTOs and transformers (INSTALLED)
- **Laravel Sanctum** - Token authentication (INSTALLED as backup)
- **Knuckles Scribe** - API documentation generator (INSTALLED)

### 🔧 API Standards
- **Response Format**: Standardized JSON responses via `ApiResponse` trait
- **Authentication**: Laravel Passport OAuth2
- **Query Building**: Spatie QueryBuilder for consistent filtering
- **CORS**: Built-in Laravel CORS (config needed)

## Working API Endpoints

### 🔓 Public Endpoints (No Auth Required)

#### Authentication
```
POST   /api/auth/register              - User registration
POST   /api/auth/register-organizer    - Organizer registration  
POST   /api/auth/login                 - User/Organizer login
POST   /api/auth/password/request-reset- Request password reset
POST   /api/auth/password/reset        - Reset password
```

#### Events/Listings
```
GET    /api/events                     - List all published events
GET    /api/events/upcoming            - Get upcoming events
GET    /api/events/featured            - Get featured events
GET    /api/events/categories          - Get all categories
GET    /api/events/search              - Search events
GET    /api/events/{id}                - Get event details
```

#### System
```
GET    /api/health                     - Health check
POST   /api/webhooks/paystack          - Paystack payment webhook
POST   /api/webhooks/mpesa             - M-Pesa payment webhook
```

### 🔒 Protected Endpoints (Auth Required)

#### User Profile
```
GET    /api/auth/me                    - Get current user profile
GET    /api/auth/user                  - Alias for me (Vue compatibility)
POST   /api/auth/logout                - Logout user
POST   /api/auth/verify-email          - Verify email address
POST   /api/auth/resend-verification   - Resend verification email
```

#### Bookings
```
GET    /api/bookings                   - List user bookings
POST   /api/bookings                   - Create new booking
GET    /api/bookings/{id}              - Get booking details
POST   /api/bookings/{id}/cancel       - Cancel booking
GET    /api/bookings/{id}/tickets      - Get booking tickets
```

#### Tickets
```
GET    /api/tickets                    - List all user tickets
GET    /api/tickets/upcoming           - Get upcoming event tickets
GET    /api/tickets/past               - Get past event tickets
GET    /api/tickets/booking/{bookingId}- Get tickets by booking
GET    /api/tickets/{id}               - Get ticket details with QR
POST   /api/tickets/{id}/transfer      - Transfer ticket
GET    /api/tickets/{id}/transfer-history - Get transfer history
GET    /api/tickets/{id}/download      - Download ticket (PDF/Pass)
```

#### Notifications
```
GET    /api/notifications              - List all notifications
GET    /api/notifications/unread       - Get unread notifications
POST   /api/notifications/{id}/read    - Mark as read
POST   /api/notifications/mark-all-read- Mark all as read
DELETE /api/notifications/{id}         - Delete notification
GET    /api/notifications/preferences  - Get preferences
PUT    /api/notifications/preferences  - Update preferences
```

#### Payments
```
POST   /api/payments/paystack/initialize - Initialize Paystack payment
POST   /api/payments/mpesa/initialize    - Initialize M-Pesa payment
GET    /api/payments/verify/{transactionId} - Verify payment status
GET    /api/payments/transactions         - Get payment history
```

#### Refunds
```
GET    /api/refunds                    - List refund requests
POST   /api/refunds                    - Request refund
GET    /api/refunds/{id}               - Get refund details
POST   /api/refunds/{id}/cancel        - Cancel refund request
GET    /api/refunds/check-eligibility/{bookingId} - Check refund eligibility
```

### 🎫 Organizer API (v1)

#### Listing Management
```
GET    /api/v1/organizer/listings      - List organizer listings
POST   /api/v1/organizer/listings/create - Create new listing
GET    /api/v1/organizer/listings/{id} - Get listing details
PUT    /api/v1/organizer/listings/{id}/update - Update listing
POST   /api/v1/organizer/listings/{id}/publish - Publish listing
DELETE /api/v1/organizer/listings/{id} - Delete listing
```

#### Ticket Validation (Scanner App)
```
POST   /api/v1/tickets/validate        - Validate QR code
POST   /api/v1/tickets/check-in        - Check-in ticket
POST   /api/v1/tickets/batch-validate  - Batch validation
POST   /api/v1/tickets/validate-by-code- Validate by code
```

#### Event Management
```
GET    /api/v1/events/{id}/manifest    - Get offline manifest
GET    /api/v1/events/{id}/check-in-stats - Get check-in statistics
```

## Request/Response Format

### Standard Success Response
```json
{
  "status": "success",
  "message": "Request successful",
  "data": {
    // Response data here
  }
}
```

### Standard Error Response
```json
{
  "status": "error",
  "message": "Error description",
  "errors": {
    // Validation errors or details
  }
}
```

### Pagination Meta
```json
{
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200
  }
}
```

## Query Parameters (Spatie QueryBuilder)

### Filtering
```
GET /api/events?filter[city]=Nairobi&filter[category_id]=123
GET /api/events?filter[price_min]=1000&filter[price_max]=5000
GET /api/events?filter[date_after]=2025-01-01
```

### Sorting
```
GET /api/events?sort=event_date        // Ascending
GET /api/events?sort=-event_date       // Descending
GET /api/events?sort=title,event_date  // Multiple sorts
```

### Including Relations
```
GET /api/events?include=organizer,category
```

### Pagination
```
GET /api/events?page=2&per_page=20
```

## Authentication

### OAuth2 Flow
1. Register user/organizer via `/api/auth/register`
2. Login via `/api/auth/login` to get access token
3. Include token in headers: `Authorization: Bearer {token}`

### Example Login Request
```bash
curl -X POST https://api.noxxi.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### Example Login Response
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": "uuid",
      "full_name": "John Doe",
      "email": "user@example.com",
      "role": "user"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJS...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

## Multi-Currency Support
The API supports multiple African currencies:
- KES - Kenyan Shilling
- NGN - Nigerian Naira
- ZAR - South African Rand
- GHS - Ghanaian Cedi
- UGX - Ugandan Shilling
- TZS - Tanzanian Shilling
- EGP - Egyptian Pound
- USD - US Dollar

## Mobile App Features Ready

### ✅ Fully Implemented
- User/Organizer registration & authentication
- Event browsing with advanced filtering
- Event search and discovery
- Category-based browsing
- Booking creation and management
- Ticket generation with QR codes
- Ticket transfers between users
- Offline ticket validation (manifest download)
- Push notifications system
- Payment gateway integration (Paystack, M-Pesa)
- Refund request system

### 🚧 Partially Implemented
- User profile management (basic only)
- Event analytics for organizers
- Revenue reporting

### ❌ Not Yet Implemented
- Social sharing integration
- User reviews and ratings
- Saved/favorite events
- Event recommendations
- In-app messaging
- Virtual event streaming

## QR Code & Ticket System

### QR Code Structure
Each ticket contains a secure QR code with:
- Ticket ID
- Event ID
- Booking ID
- HMAC-SHA256 signature for security

### Offline Validation
Organizers can download event manifests for offline ticket validation:
```
GET /api/v1/events/{id}/manifest
```

Returns all valid tickets with signatures for offline verification.

## Rate Limiting
- Default: 60 requests per minute per IP
- Authenticated: 120 requests per minute per user
- Webhook endpoints: Unlimited

## Development Tools Available

### API Documentation
Generate interactive docs:
```bash
php artisan scribe:generate
```
Access at: `http://localhost/docs`

### Testing Endpoints
Use Laravel Telescope for debugging (dev only)

## Recommended Improvements

### High Priority
1. **Configure CORS** - Add cors.php configuration for mobile app access
2. **Implement Laravel Telescope** - For API monitoring in development
3. **Add Redis Caching** - For frequently accessed data (categories, featured events)
4. **Create Data Transformers** - Use Spatie Laravel Data for consistent responses

### Medium Priority
1. **Add GraphQL Support** - For more efficient mobile queries
2. **Implement WebSockets** - For real-time notifications
3. **Add API Versioning** - Proper version management
4. **Create SDK/Client Libraries** - Flutter/Dart package for mobile

### Low Priority
1. **Add API Analytics** - Track usage patterns
2. **Implement Request Throttling** - Per-endpoint rate limits
3. **Add Response Compression** - Gzip/Brotli for faster transfers

## Security Considerations
- All endpoints use HTTPS
- OAuth2 token expiration: 24 hours
- QR codes use HMAC-SHA256 signatures
- SQL injection protection via Eloquent ORM
- XSS protection via Laravel's built-in escaping
- CSRF protection for web endpoints

## Contact & Support
For API issues or questions, contact the development team.