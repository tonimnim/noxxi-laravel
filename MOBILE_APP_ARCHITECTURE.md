# Noxxi Mobile App Architecture

## Executive Summary
A unified Flutter application serving both **Users** and **Organizers** with role-based features, leveraging the Laravel backend for all business logic while maintaining minimal client-side complexity.

## Core Principles

### 1. Backend-First Philosophy
- **Laravel handles ALL business logic** - the app is a presentation layer
- **No complex calculations** in the app (commissions, fees, validations)
- **API-driven** - all data flows through REST APIs
- **Stateless operations** - no local business rules

### 2. Feature-First Architecture
```
lib/
├── core/                    # Shared foundation
├── features/               # Feature modules
│   ├── auth/              # Shared authentication
│   ├── events/            # Event browsing (Users)
│   ├── tickets/           # Ticket management (Users)
│   ├── bookings/          # Booking flow (Users)
│   ├── organizer_dashboard/  # Organizer analytics
│   ├── organizer_events/     # Event management (Organizers)
│   ├── organizer_payouts/    # Payout management (Organizers)
│   └── shared/              # Shared features
└── main.dart
```

### 3. Single App, Role-Based Navigation
- **One app installation** for both user types
- **Dynamic UI based on user role** after login
- **Shared components** with role-specific behaviors
- **Unified update cycle** - one app to maintain

## Detailed Architecture

### Core Layer (`lib/core/`)
```
core/
├── api/
│   ├── api_client.dart          # Dio instance, interceptors
│   ├── api_endpoints.dart       # All endpoint constants
│   └── api_exceptions.dart     # Error handling
├── storage/
│   ├── secure_storage.dart     # OAuth tokens
│   ├── cache_manager.dart      # Temporary data
│   └── preferences.dart        # User settings
├── models/
│   ├── user.dart               # Shared user model
│   ├── api_response.dart       # Standard API response
│   └── pagination.dart         # Pagination metadata
├── routing/
│   ├── app_router.dart         # GoRouter configuration
│   ├── route_guards.dart       # Auth & role checks
│   └── deep_links.dart        # Handle external links
├── theme/
│   ├── app_colors.dart         # Brand colors
│   ├── app_typography.dart     # Text styles
│   └── app_theme.dart          # Material theme
├── utils/
│   ├── currency_formatter.dart # Multi-currency support
│   ├── date_formatter.dart    # Date/time formatting
│   ├── validators.dart         # Input validation
│   └── constants.dart         # App-wide constants
└── widgets/
    ├── app_button.dart         # Standard button
    ├── app_card.dart          # Card component
    ├── loading_overlay.dart   # Loading states
    └── error_view.dart        # Error handling UI
```

### Feature Structure Pattern
Each feature follows this structure:
```
feature_name/
├── data/
│   ├── models/              # Feature-specific models
│   ├── repositories/        # API calls
│   └── datasources/        # Remote/local data
├── domain/
│   ├── entities/           # Business entities
│   └── usecases/          # Business logic (minimal)
├── presentation/
│   ├── screens/           # Full screens
│   ├── widgets/           # Feature widgets
│   ├── controllers/       # GetX/Riverpod/Bloc
│   └── bindings/         # Dependency injection
└── feature_name.dart     # Public exports
```

## Key Features Implementation

### 1. Authentication (`lib/features/auth/`)
```
auth/
├── data/
│   ├── models/
│   │   ├── login_request.dart
│   │   ├── register_request.dart
│   │   └── auth_token.dart
│   └── repositories/
│       └── auth_repository.dart
├── presentation/
│   ├── screens/
│   │   ├── splash_screen.dart      # Role detection
│   │   ├── login_screen.dart       # Unified login
│   │   ├── register_screen.dart    # User registration
│   │   └── organizer_register_screen.dart
│   ├── widgets/
│   │   ├── role_selector.dart     # User/Organizer toggle
│   │   └── social_login_buttons.dart
│   └── controllers/
│       └── auth_controller.dart   # Auth state management
```

**Key Implementation:**
```dart
class AuthController extends GetxController {
  final currentUser = Rxn<User>();
  final isOrganizer = false.obs;
  
  Future<void> login(String email, String password) async {
    final response = await authRepo.login(email, password);
    currentUser.value = response.user;
    isOrganizer.value = response.user.hasOrganizerAccount;
    
    // Navigate based on role
    if (isOrganizer.value) {
      Get.offAllNamed(Routes.ORGANIZER_DASHBOARD);
    } else {
      Get.offAllNamed(Routes.USER_HOME);
    }
  }
}
```

### 2. Event Discovery (`lib/features/events/`) - Users Only
```
events/
├── data/
│   ├── models/
│   │   ├── event.dart
│   │   ├── event_filter.dart
│   │   └── category.dart
│   └── repositories/
│       └── event_repository.dart
├── presentation/
│   ├── screens/
│   │   ├── events_home_screen.dart
│   │   ├── event_details_screen.dart
│   │   ├── event_search_screen.dart
│   │   └── category_events_screen.dart
│   └── widgets/
│       ├── event_card.dart
│       ├── event_filter_sheet.dart
│       └── featured_carousel.dart
```

### 3. Tickets (`lib/features/tickets/`) - Users Only
```
tickets/
├── data/
│   ├── models/
│   │   ├── ticket.dart
│   │   └── qr_data.dart
│   └── repositories/
│       └── ticket_repository.dart
├── domain/
│   └── usecases/
│       └── offline_ticket_validator.dart
├── presentation/
│   ├── screens/
│   │   ├── my_tickets_screen.dart
│   │   └── ticket_details_screen.dart
│   └── widgets/
│       ├── ticket_card.dart
│       ├── qr_code_view.dart
│       └── ticket_transfer_dialog.dart
```

**Offline Support:**
```dart
class OfflineTicketStorage {
  Future<void> cacheTickets(List<Ticket> tickets) async {
    // Store tickets with QR data for offline access
    final box = await Hive.openBox('tickets');
    for (var ticket in tickets) {
      box.put(ticket.id, ticket.toJson());
    }
  }
  
  Future<List<Ticket>> getOfflineTickets() async {
    // Retrieve cached tickets when offline
  }
}
```

### 4. Organizer Dashboard (`lib/features/organizer_dashboard/`)
```
organizer_dashboard/
├── data/
│   ├── models/
│   │   ├── dashboard_stats.dart
│   │   ├── revenue_data.dart
│   │   └── booking_analytics.dart
│   └── repositories/
│       └── analytics_repository.dart
├── presentation/
│   ├── screens/
│   │   ├── dashboard_screen.dart
│   │   ├── analytics_screen.dart
│   │   └── revenue_screen.dart
│   └── widgets/
│       ├── stats_card.dart
│       ├── revenue_chart.dart
│       └── recent_bookings.dart
```

### 5. Shared Features (`lib/features/shared/`)
```
shared/
├── notifications/          # Push notifications
├── settings/              # App settings
├── profile/              # User profile
└── support/              # Help & support
```

## Navigation Structure

### Role-Based Bottom Navigation

**User Navigation:**
```dart
final userTabs = [
  BottomNavItem(icon: Icons.home, label: 'Home', route: '/home'),
  BottomNavItem(icon: Icons.search, label: 'Search', route: '/search'),
  BottomNavItem(icon: Icons.confirmation_num, label: 'Tickets', route: '/tickets'),
  BottomNavItem(icon: Icons.person, label: 'Profile', route: '/profile'),
];
```

**Organizer Navigation:**
```dart
final organizerTabs = [
  BottomNavItem(icon: Icons.dashboard, label: 'Dashboard', route: '/organizer/dashboard'),
  BottomNavItem(icon: Icons.event, label: 'Events', route: '/organizer/events'),
  BottomNavItem(icon: Icons.payments, label: 'Payouts', route: '/organizer/payouts'),
  BottomNavItem(icon: Icons.settings, label: 'Settings', route: '/organizer/settings'),
];
```

## State Management Strategy

### GetX Pattern (Recommended)
```dart
// Simple, reactive, minimal boilerplate
class EventController extends GetxController {
  final _repository = Get.find<EventRepository>();
  final events = <Event>[].obs;
  final isLoading = false.obs;
  
  @override
  void onInit() {
    super.onInit();
    fetchEvents();
  }
  
  Future<void> fetchEvents() async {
    isLoading.value = true;
    try {
      events.value = await _repository.getEvents();
    } finally {
      isLoading.value = false;
    }
  }
}
```

## API Integration Pattern

### Repository Pattern
```dart
class EventRepository {
  final ApiClient _apiClient;
  
  EventRepository(this._apiClient);
  
  Future<List<Event>> getEvents({Map<String, dynamic>? filters}) async {
    final response = await _apiClient.get(
      ApiEndpoints.events,
      queryParameters: filters,
    );
    
    // Laravel returns standardized response
    if (response.data['status'] == 'success') {
      return (response.data['data']['events'] as List)
          .map((e) => Event.fromJson(e))
          .toList();
    }
    
    throw ApiException(response.data['message']);
  }
}
```

## Offline Capabilities

### Critical Offline Features
1. **Ticket Display** - Show cached tickets with QR codes
2. **Event Browsing** - Cache recent/featured events
3. **Queue Management** - Store actions to sync when online

```dart
class OfflineQueueManager {
  final _queue = <QueuedAction>[];
  
  void addToQueue(QueuedAction action) {
    _queue.add(action);
    _processPendingActions();
  }
  
  Future<void> _processPendingActions() async {
    if (await isOnline()) {
      for (var action in _queue) {
        await action.execute();
        _queue.remove(action);
      }
    }
  }
}
```

## Security Considerations

### Token Management
```dart
class SecureTokenStorage {
  static const _storage = FlutterSecureStorage();
  
  static Future<void> saveToken(String token) async {
    await _storage.write(key: 'auth_token', value: token);
  }
  
  static Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }
  
  static Future<void> clearToken() async {
    await _storage.delete(key: 'auth_token');
  }
}
```

### API Security
- OAuth2 Bearer tokens for all requests
- Automatic token refresh
- Certificate pinning for production
- No sensitive data in local storage

## Performance Optimizations

### Image Caching
```dart
CachedNetworkImage(
  imageUrl: event.coverImageUrl,
  placeholder: (context, url) => ShimmerLoading(),
  errorWidget: (context, url, error) => Icon(Icons.error),
  cacheManager: DefaultCacheManager(),
)
```

### Pagination
```dart
class PaginatedListView<T> extends StatefulWidget {
  final Future<PaginatedResponse<T>> Function(int page) onLoadMore;
  final Widget Function(T item) itemBuilder;
  
  // Infinite scroll with automatic loading
}
```

## Testing Strategy

### Unit Tests
```
test/
├── core/
│   └── api/
│       └── api_client_test.dart
├── features/
│   ├── auth/
│   │   └── auth_repository_test.dart
│   └── events/
│       └── event_controller_test.dart
```

### Widget Tests
```dart
testWidgets('EventCard displays correctly', (tester) async {
  await tester.pumpWidget(
    MaterialApp(
      home: EventCard(event: mockEvent),
    ),
  );
  
  expect(find.text(mockEvent.title), findsOneWidget);
  expect(find.text(mockEvent.venue), findsOneWidget);
});
```

## Build Configuration

### Environment Management
```dart
class Environment {
  static const String apiUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'https://api.noxxi.com',
  );
  
  static const bool isProduction = bool.fromEnvironment('IS_PRODUCTION');
}
```

### Build Commands
```bash
# Development
flutter run --dart-define=API_URL=http://localhost:8000/api

# Production
flutter build apk --dart-define=API_URL=https://api.noxxi.com/api --dart-define=IS_PRODUCTION=true
```

## Deployment Strategy

### Version Management
- Use semantic versioning (1.0.0)
- Force update for breaking changes
- Optional update for features
- API version checking on app launch

### Platform-Specific
```
android/
├── app/
│   └── src/
│       ├── main/          # Production
│       ├── staging/       # Staging environment
│       └── debug/         # Development
ios/
└── Runner/
    ├── Info.plist
    └── GoogleService-Info.plist
```

## Recommended Packages

### Essential Dependencies
```yaml
dependencies:
  # State Management
  get: ^4.6.5
  
  # Networking
  dio: ^5.3.2
  connectivity_plus: ^5.0.1
  
  # Storage
  flutter_secure_storage: ^9.0.0
  hive_flutter: ^1.1.0
  
  # UI Components
  cached_network_image: ^3.3.0
  flutter_svg: ^2.0.7
  shimmer: ^3.0.0
  
  # Utilities
  intl: ^0.18.1
  url_launcher: ^6.1.14
  share_plus: ^7.2.1
  
  # QR & Barcode
  qr_flutter: ^4.1.0
  mobile_scanner: ^3.4.1
  
  # Payments (if needed)
  flutter_paystack: ^1.0.7
```

## Migration Path

### Phase 1: Core Features (MVP)
1. Authentication (login/register)
2. Event browsing and search
3. Ticket purchase flow
4. Ticket display with QR

### Phase 2: Organizer Features
1. Organizer dashboard
2. Basic event management
3. Booking views
4. Revenue tracking

### Phase 3: Advanced Features
1. Offline support
2. Push notifications
3. Payment integration
4. Analytics

### Phase 4: Optimization
1. Performance tuning
2. Code splitting
3. A/B testing
4. Advanced caching

## Conclusion

This architecture provides:
- **Minimal redundancy** - Laravel handles all logic
- **Clear separation** - User vs Organizer features
- **Scalability** - Easy to add features
- **Maintainability** - Clear structure and patterns
- **Performance** - Optimized for African market (offline, low bandwidth)
- **Single codebase** - One app for both user types

The app acts as a **smart presentation layer** that consumes your robust Laravel APIs, avoiding duplicate business logic while providing an excellent native experience.