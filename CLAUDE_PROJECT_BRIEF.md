# Noxxi Platform - Complete Project Brief for Claude

## Project Overview
You'll be working on **Noxxi**, a comprehensive event ticketing and management platform designed specifically for the African market. The platform consists of a robust Laravel backend (already built) and a Flutter mobile app (to be built). Noxxi connects event organizers with attendees across multiple African countries, supporting local payment methods and currencies.

## Current Status
- ✅ **Laravel Backend**: Fully built with REST APIs, admin panels, payment integrations
- ✅ **Web Application**: Vue.js frontend for users 
- ✅ **Filament Admin Panels**: Three separate panels (Admin, Organizer, User)
- 🚧 **Flutter Mobile App**: To be built following feature-first architecture

## Technology Stack

### Backend (Completed)
- **Framework**: Laravel 12.x (latest structure)
- **Database**: PostgreSQL with UUID primary keys and JSONB fields
- **Authentication**: Laravel Passport OAuth2
- **Admin Interface**: Filament 3.x (3 separate panels)
- **Payments**: Paystack integration, M-Pesa ready
- **Real-time**: Pusher for notifications
- **Queue**: Laravel Jobs for async processing
- **Storage**: Local disk with public access for images

### Frontend Web (Completed)
- **Framework**: Vue.js 3.x
- **Styling**: Tailwind CSS 4.x
- **Build Tool**: Vite
- **Authentication**: Session-based for web, OAuth2 for API

### Mobile App (To Build)
- **Framework**: Flutter (latest stable)
- **State Management**: GetX
- **API Client**: Dio with interceptors
- **Storage**: Flutter Secure Storage for tokens, Hive for cache
- **Offline**: Support for ticket viewing and validation

## Business Model

### Platform Overview
Noxxi is a marketplace where:
1. **Event Organizers** create and manage events, sell tickets, receive payouts
2. **Users** browse events, purchase tickets, manage bookings
3. **Platform** takes commission (configurable per organizer/event)

### Key Business Rules
1. **Commission Structure**:
   - Default 10% platform fee
   - Configurable per organizer (premium organizers get lower rates)
   - Override possible per event

2. **Payout System**:
   - Manual approval required (no automatic payouts)
   - Admin must approve each payout request
   - Multiple payment methods (bank transfer, mobile money)
   - No minimum payout amount

3. **Multi-Currency Support**:
   - KES (Kenya), NGN (Nigeria), ZAR (South Africa), GHS (Ghana)
   - UGX (Uganda), TZS (Tanzania), EGP (Egypt), USD
   - Each organizer has default currency
   - Prices displayed in local currency

## Database Architecture

### Key Design Decisions
1. **UUIDs as Primary Keys**: All tables use UUID primary keys
2. **JSONB Fields**: Complex data stored as JSONB (ticket_types, policies, media)
3. **Soft Deletes**: Most models use soft deletes
4. **Multi-tenancy**: Organizer-based data isolation

### Core Models
```
Users (id: uuid)
├── has one → Organizer (premium_status, commission_rate, payout_details)
├── has many → Bookings
├── has many → Tickets (assigned_to)
└── has many → Transactions

Events (id: uuid, organizer_id, category_id)
├── belongs to → Organizer
├── belongs to → Category (hierarchical: parent/child)
├── has many → Bookings
├── has many → Tickets
└── JSONB fields → ticket_types, policies, media, socials

Bookings (id: uuid, user_id, event_id)
├── has many → Tickets
├── has many → Transactions
└── expires_at → Automatic cleanup if unpaid

Tickets (id: uuid, booking_id, event_id, assigned_to)
├── QR code → Generated on-demand with HMAC signature
└── Status → valid, used, transferred, cancelled

Payouts (id: uuid, organizer_id)
├── Status → pending, approved, processing, completed, failed
├── Admin approval → approved_by, approved_at
└── Tracking → reference_number, provider_reference
```

## API Structure

### Design Principles
1. **Standardized Responses**:
```json
{
  "status": "success|error",
  "message": "Operation description",
  "data": {},
  "errors": {}
}
```

2. **Authentication**: Bearer token in Authorization header
3. **Pagination**: Consistent meta structure with pagination details
4. **Filtering**: Using Spatie QueryBuilder for complex filters
5. **Error Codes**: Proper HTTP status codes (401, 403, 422, etc.)

### Key Endpoints
- `/api/auth/*` - Authentication (login, register, verify)
- `/api/events/*` - Event CRUD and search
- `/api/bookings/*` - Booking creation and management
- `/api/tickets/*` - Ticket retrieval and validation
- `/api/payments/*` - Payment initialization and verification
- `/api/organizer/*` - Organizer-specific endpoints
- `/api/v1/*` - Versioned endpoints for mobile app

## Mobile App Architecture

### Core Philosophy
1. **Single App, Dual Experience**: One app serves both users and organizers
2. **Backend-First**: Laravel handles ALL business logic, app is presentation only
3. **Feature-First Structure**: Organized by features, not layers
4. **Offline-First**: Critical features work offline (tickets, QR codes)

### Feature Categories

#### User Features
- **Event Discovery**: Browse, search, filter, map view
- **Booking Flow**: Ticket selection, seat picking, checkout
- **Ticket Management**: View, transfer, download PDF
- **Profile**: Preferences, payment methods, history

#### Organizer Features  
- **Dashboard**: Revenue, analytics, recent activity
- **Event Management**: Create, edit, publish, cancel
- **Financial**: Payouts, transactions, commissions
- **Check-in**: QR scanner for ticket validation

#### Shared Features
- **Authentication**: Email/password, OTP, biometric
- **Notifications**: Push, in-app, preferences
- **Support**: Help center, chat, feedback
- **Settings**: Theme, language, offline mode

### Technical Implementation

#### State Management (GetX)
```dart
class EventController extends GetxController {
  final events = <Event>[].obs;
  final isLoading = false.obs;
  
  Future<void> fetchEvents() async {
    isLoading.value = true;
    events.value = await repository.getEvents();
    isLoading.value = false;
  }
}
```

#### API Integration
```dart
class ApiClient {
  // All API calls go through central client
  // Automatic token refresh
  // Standardized error handling
  // Request/response interceptors
}
```

#### Offline Support
```dart
// Cache critical data
await HiveBox.tickets.put(ticket.id, ticket.toJson());
// Generate QR codes locally with stored signature
final qrData = QrService.generateSecure(ticket);
```

## Security Requirements

### Backend Security
1. **Never store sensitive keys in code** - Use environment variables
2. **All inputs validated** - Form requests with custom rules
3. **Authorization checks** - Policies and gates
4. **Rate limiting** - API throttling per user/IP
5. **HTTPS only** in production

### Mobile Security
1. **OAuth2 tokens** in secure storage
2. **No business logic** in app code
3. **Certificate pinning** for production
4. **Obfuscation** for release builds
5. **Offline validation** using HMAC signatures

## Payment Integration

### Supported Methods
1. **Paystack**: Card payments, bank transfers
2. **M-Pesa**: Mobile money (Kenya)
3. **Bank Transfer**: Direct bank deposits
4. **USSD**: For users without smartphones

### Payment Flow
1. User selects tickets → Creates booking (expires in 15 min)
2. Initialize payment → Get payment URL/reference
3. User completes payment → Webhook received
4. Verify payment → Create tickets
5. Send confirmation → Email with tickets

## QR Code System

### Security Features
1. **Dynamic Generation**: QR codes generated on-demand, never stored
2. **HMAC Signature**: Each QR signed with event secret key
3. **Offline Validation**: Download manifest for offline events
4. **One-time Use**: Mark as used after scanning

### QR Structure
```json
{
  "ticket_id": "uuid",
  "event_id": "uuid", 
  "code": "TIX-XXXXX",
  "signature": "hmac_sha256_hash"
}
```

## Development Guidelines

### Laravel Conventions
1. **Use artisan commands** for file creation
2. **Follow existing patterns** - Check similar files first
3. **Use Eloquent relationships** - Avoid raw queries
4. **Cache expensive operations** - Clear after updates
5. **Use Form Requests** for validation
6. **API Resources** for response transformation

### Flutter Conventions
1. **Feature-first organization** - Each feature is self-contained
2. **Repository pattern** for API calls
3. **GetX for state** - Reactive and simple
4. **Consistent styling** - Use theme constants
5. **Error handling** - User-friendly messages

### Testing Requirements
1. **Feature tests** for API endpoints
2. **Unit tests** for services
3. **Widget tests** for Flutter UI
4. **Integration tests** for critical flows

## Deployment Configuration

### Environment Variables
```env
# Payment
PAYSTACK_PUBLIC_KEY=pk_test_xxx
PAYSTACK_SECRET_KEY=sk_test_xxx

# OAuth
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=

# Services
PUSHER_APP_ID=
AFRICASTALKING_API_KEY=
```

### Server Requirements
- PHP 8.2+
- PostgreSQL 14+
- Redis (optional but recommended)
- SSL certificate (required for production)

## Common Development Tasks

### Adding New Feature
1. Create migration with UUIDs
2. Create model with proper relationships
3. Add Filament resource if needed
4. Create API endpoints with filtering
5. Add service class for complex logic
6. Create Flutter feature module
7. Write tests

### Implementing Payment Gateway
1. Create service in `app/Services/`
2. Implement initialize() and verify() methods
3. Add webhook handler
4. Update PaymentController
5. Add to Flutter payment options

## African Market Considerations

### Optimization Priorities
1. **Low bandwidth** - Compress images, paginate aggressively
2. **Offline capability** - Cache critical data
3. **Mobile-first** - Most users on phones
4. **Local payments** - M-Pesa, mobile money priority
5. **Multi-currency** - Display in local currency

### Cultural Adaptations
1. Date/time in local formats
2. Phone number validation per country
3. Address formats vary by region
4. Payment preferences differ by country

## Critical Files to Review

### Backend Structure
```
app/
├── Filament/          # Admin panels (Admin, Organizer, User)
├── Http/
│   ├── Controllers/
│   │   ├── Api/      # API endpoints
│   │   └── Web/      # Web controllers
│   └── Middleware/   # Security, CORS
├── Models/           # Eloquent models with UUIDs
├── Services/         # Business logic
│   ├── PaymentService.php
│   ├── PayoutService.php
│   ├── TicketService.php
│   └── NotificationService.php
└── Providers/        # Service providers

database/
├── migrations/       # All use UUIDs
└── seeders/         # Categories, test data

.ai/                  # Project guidelines
├── guidelines/      # Technology-specific rules
└── *.md            # Architecture docs
```

### Key Documentation Files
- `CLAUDE.md` - Project instructions and rules
- `MOBILE_APP_ARCHITECTURE.md` - Complete mobile app structure
- `.ai/noxxi-project.md` - Business rules and patterns
- `.ai/noxxi-api.md` - API development guidelines
- `.ai/noxxi-filament.md` - Admin panel guidelines

## Testing Credentials
```
Test Organizer: anto@gmail.com / 8800kl
Test OTP: 123456 (development only)
Paystack Test Card: 4084084084084081
```

## Important Warnings

### Never Do
1. Store API keys in code
2. Create files unless necessary
3. Add business logic to mobile app
4. Use raw SQL queries
5. Skip validation
6. Cache sensitive data
7. Add automatic/scheduled payouts

### Always Do  
1. Use existing patterns
2. Validate all inputs
3. Check authorization
4. Handle errors gracefully
5. Use UUID primary keys
6. Cast JSONB fields as arrays
7. Clear cache after updates
8. Follow feature-first structure

## Your Task
You're building the Flutter mobile application following the architecture defined in `MOBILE_APP_ARCHITECTURE.md`. The Laravel backend is complete with all APIs ready. Focus on creating a clean, efficient mobile app that serves as a presentation layer for the robust backend, maintaining the feature-first architecture and ensuring excellent user experience for both regular users and event organizers in the African market.

## Success Criteria
1. **Clean Architecture**: Feature-first, no redundant code
2. **Dual Experience**: Seamless switching between user/organizer modes
3. **Offline Support**: Critical features work without internet
4. **Performance**: Optimized for low-end devices and slow networks
5. **Security**: Proper token management, no sensitive data storage
6. **User Experience**: Intuitive, follows platform conventions
7. **Maintainability**: Clear structure, well-documented code

Remember: The mobile app should be a thin client that leverages the Laravel backend for all business logic. Think of it as a beautiful, responsive UI that consumes the comprehensive REST APIs already built.