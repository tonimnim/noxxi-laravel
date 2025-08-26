# NOXXI MOBILE API DOCUMENTATION

## Base URL
- Development: `http://localhost:8000/api`
- Production: `https://api.noxxi.com/api`

## Authentication
All protected endpoints require Bearer token in header:
```
Authorization: Bearer {access_token}
```

## Available Mobile Endpoints

### 1. Authentication & User Management

#### Register User
```
POST /api/auth/register
Body: {
  "full_name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "phone_number": "+254712345678"
}
```

#### Login
```
POST /api/auth/login
Body: {
  "email": "john@example.com",
  "password": "password123"
}
Returns: {
  "access_token": "...",
  "user": {...}
}
```

#### Get Current User
```
GET /api/auth/me
```

#### Logout
```
POST /api/auth/logout
```

#### Refresh Token
```
POST /api/auth/refresh
Body: {
  "refresh_token": "..."
}
```

### 2. Event Discovery

#### List All Events
```
GET /api/events
Query params:
- page=1
- per_page=20
- city=Nairobi
- category_id=uuid
- date_from=2025-01-01
- date_to=2025-12-31
- min_price=0
- max_price=10000
```

#### Get Single Event
```
GET /api/events/{id}
```

#### Search Events
```
GET /api/events/search?q=jazz&city=Nairobi
```

#### Get Trending Events
```
GET /api/events/trending
```

#### Get Featured Events
```
GET /api/events/featured
```

#### Get Upcoming Events
```
GET /api/events/upcoming
```

#### Get Events by Category
```
GET /api/events/categories/{slug}
GET /api/cinema
GET /api/sports
GET /api/experiences
```

#### Get Similar Events
```
GET /api/events/{id}/similar
```

### 3. Categories & Cities

#### Get All Categories
```
GET /api/events/categories
```

#### Get Cities
```
GET /api/cities
GET /api/cities/popular
GET /api/cities/search?q=nairobi
```

### 4. Bookings

#### Create Booking
```
POST /api/bookings
Body: {
  "event_id": "uuid",
  "ticket_types": [
    {
      "name": "VIP",
      "quantity": 2,
      "price": 5000
    }
  ],
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+254712345678"
}
```

#### Get User's Bookings
```
GET /api/bookings
```

#### Get Single Booking
```
GET /api/bookings/{id}
```

#### Cancel Booking
```
POST /api/bookings/{id}/cancel
```

#### Get Booking Tickets
```
GET /api/bookings/{id}/tickets
```

### 5. Tickets

#### Get User's Tickets
```
GET /api/tickets
```

#### Get Upcoming Tickets
```
GET /api/tickets/upcoming
```

#### Get Past Tickets
```
GET /api/tickets/past
```

#### Get Single Ticket with QR Code
```
GET /api/tickets/{id}
Returns: {
  "ticket": {
    "id": "uuid",
    "ticket_code": "EVT-123456",
    "qr_code": "base64_encoded_qr_image",
    "event": {...},
    "valid_from": "2025-01-01 10:00:00",
    "valid_until": "2025-01-01 23:00:00"
  }
}
```

#### Download Ticket PDF
```
GET /api/tickets/{id}/download
```

#### Transfer Ticket
```
POST /api/tickets/{id}/transfer
Body: {
  "recipient_email": "recipient@example.com",
  "recipient_name": "Jane Doe",
  "message": "Enjoy the event!"
}
```

### 6. Payments

#### Initialize Paystack Payment
```
POST /api/payments/paystack/initialize
Body: {
  "transaction_id": "uuid"
}
Returns: {
  "authorization_url": "https://checkout.paystack.com/...",
  "reference": "REF_123456"
}
```

#### Initialize M-Pesa Payment
```
POST /api/payments/mpesa/initialize
Body: {
  "transaction_id": "uuid",
  "phone_number": "254712345678"
}
```

#### Verify Payment
```
GET /api/payments/verify/{transaction_id}
```

#### Get Payment History
```
GET /api/payments/transactions
```

### 7. Refunds

#### Request Refund
```
POST /api/refunds
Body: {
  "booking_id": "uuid",
  "reason": "Cannot attend the event",
  "requested_amount": 5000,
  "customer_message": "Family emergency"
}
```

#### Check Refund Eligibility
```
GET /api/refunds/check-eligibility/{booking_id}
Returns: {
  "eligible": true,
  "max_refund_amount": 5000,
  "refund_percentage": 100,
  "policy_description": "Full refund available",
  "days_until_event": 10
}
```

#### Get Refund Requests
```
GET /api/refunds
```

#### Get Single Refund Request
```
GET /api/refunds/{id}
```

#### Cancel Refund Request
```
POST /api/refunds/{id}/cancel
```

### 8. Notifications

#### Get Notifications
```
GET /api/notifications
```

#### Get Unread Notifications
```
GET /api/notifications/unread
```

#### Mark Notification as Read
```
POST /api/notifications/{id}/read
```

#### Mark All as Read
```
POST /api/notifications/mark-all-read
```

#### Get/Update Notification Preferences
```
GET /api/notifications/preferences
PUT /api/notifications/preferences
Body: {
  "email_notifications": true,
  "push_notifications": true,
  "sms_notifications": false
}
```

### 9. Location Services

#### Detect User Location
```
GET /api/location/detect
Returns: {
  "city": "Nairobi",
  "country": "Kenya",
  "currency": "KES"
}
```

### 10. Ticket Validation (For Organizers/Scanners)

#### Validate Ticket by QR Code
```
POST /api/v1/tickets/validate
Body: {
  "qr_data": "base64_encoded_qr_data"
}
```

#### Check-in Ticket
```
POST /api/v1/tickets/check-in
Body: {
  "ticket_id": "uuid",
  "entry_gate": "Main Gate"
}
```

#### Get Event Manifest (Offline Mode)
```
GET /api/v1/events/{id}/manifest
Returns: {
  "event": {...},
  "tickets": [...],
  "validation_keys": {...},
  "last_updated": "2025-01-01 12:00:00"
}
```

## Response Format

### Success Response
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": {...}
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description",
  "errors": {
    "field": ["Error message"]
  }
}
```

### Paginated Response
```json
{
  "status": "success",
  "data": {
    "items": [...],
    "meta": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 20,
      "total": 200
    }
  }
}
```

## HTTP Status Codes
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 429: Too Many Requests
- 500: Server Error

## Rate Limiting
- General API: 60 requests per minute
- Auth endpoints: 10 requests per minute
- Search: 30 requests per minute

## Supported Currencies
- KES (Kenyan Shilling)
- NGN (Nigerian Naira)
- ZAR (South African Rand)
- GHS (Ghanaian Cedi)
- UGX (Ugandan Shilling)
- TZS (Tanzanian Shilling)
- EGP (Egyptian Pound)
- USD (US Dollar)

## Refund Policy
- More than 7 days before event: 100% refund
- 4-7 days before event: 75% refund (service fee non-refundable)
- 1-3 days before event: 50% refund (service fee non-refundable)
- Less than 24 hours: No refund

## Testing
Use test environment with test API keys:
- Paystack Test Public Key: pk_test_...
- Test cards available in Paystack documentation

## Mobile App Specific Features
1. **Offline Ticket Validation**: Download event manifest for offline scanning
2. **QR Code Generation**: Each ticket has unique QR code with HMAC signature
3. **Push Notifications**: Real-time updates for bookings and events
4. **Location-based Discovery**: Auto-detect user city and currency
5. **Multi-currency Support**: Prices shown in user's local currency