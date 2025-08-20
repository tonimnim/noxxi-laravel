# Flutter API Configuration

## Connection Details
- **Base URL**: `http://192.168.0.103:8000/api`
- **Desktop IP**: `192.168.0.103`
- **Port**: `8000`

## How to Start the API Server

On the desktop machine, run:
```bash
./serve-network.sh
```

Or manually:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Flutter App Configuration

In your Flutter app, update your API service to use:

```dart
class ApiService {
  static const String baseUrl = 'http://192.168.0.103:8000/api';
  
  // For development, you might want to make this configurable
  static const String devBaseUrl = 'http://192.168.0.103:8000/api';
  static const String prodBaseUrl = 'https://api.noxxi.com/api'; // Future production URL
}
```

## Authentication & Authorization Endpoints

### 🔐 Authentication (Public)

#### Login
- **POST** `/api/auth/login`
- Body:
```json
{
  "email": "user@example.com",
  "password": "password"
}
```
- Response:
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "Bearer token...",
    "refresh_token": "...",
    "expires_at": "2125-08-17T14:46:07+00:00"
  }
}
```

#### Register User
- **POST** `/api/auth/register`
- Body:
```json
{
  "full_name": "John Doe",
  "email": "john@example.com",
  "phone_number": "+254712345678",
  "password": "password",
  "password_confirmation": "password"
}
```
- Response: Same as login

#### Forgot Password
- **POST** `/api/auth/password/request-reset`
- Body:
```json
{
  "email": "user@example.com"
}
```
- Response: Sends OTP code to email
```json
{
  "status": "success",
  "message": "Password reset code sent",
  "data": null
}
```

#### Reset Password
- **POST** `/api/auth/password/reset`
- Body:
```json
{
  "email": "user@example.com",
  "code": "123456",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```
- Response:
```json
{
  "status": "success",
  "message": "Password reset successfully",
  "data": null
}
```

#### Refresh Token
- **POST** `/api/auth/refresh`
- Body:
```json
{
  "refresh_token": "refresh_token_here"
}
```
- Response: New access token

### 🔒 Authenticated Endpoints (Requires Bearer Token)

#### Get Current User
- **GET** `/api/auth/me` or `/api/auth/user`
- Headers: `Authorization: Bearer {token}`
- Response:
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": "uuid",
      "full_name": "Test User",
      "email": "test@test.com",
      "phone_number": "+254700123456",
      "role": "user",
      "city": "Nairobi",
      "country_code": "KE",
      "notification_preferences": {...}
    }
  }
}
```

#### Update Profile
- **PUT** `/api/user/profile`
- Headers: `Authorization: Bearer {token}`
- Body (all fields optional):
```json
{
  "full_name": "Updated Name",
  "phone_number": "+254700999999",
  "city": "Mombasa",
  "country": "Kenya",
  "notification_preferences": {
    "email": true,
    "sms": false,
    "push": true
  }
}
```

#### Change Password
- **POST** `/api/user/change-password`
- Headers: `Authorization: Bearer {token}`
- Body:
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword",
  "new_password_confirmation": "newpassword"
}
```

#### Logout
- **POST** `/api/auth/logout`
- Headers: `Authorization: Bearer {token}`
- Response:
```json
{
  "status": "success",
  "message": "Logged out successfully",
  "data": null
}
```

#### Email Verification
- **POST** `/api/auth/verify-email`
- Headers: `Authorization: Bearer {token}`
- Body:
```json
{
  "code": "123456"
}
```

#### Resend Verification
- **POST** `/api/auth/resend-verification`
- Headers: `Authorization: Bearer {token}`

### ❌ NOT Available for Mobile
- **Organizer Registration** - Organizers must register via web portal at http://192.168.0.103:8000/register-organizer

## Main API Endpoints

### Events/Listings
- **GET** `/api/events` - List all events (paginated)
- **GET** `/api/events/{id}` - Get single event
- **GET** `/api/events/trending` - Get trending/popular events
- **GET** `/api/events/trending?category=sports` - Get trending events by category
- **GET** `/api/events/trending?city=Nairobi` - Get trending events by city
- **GET** `/api/events/trending?category=concerts&city=Lagos` - Filter by both category and city
- **GET** `/api/events/{id}/similar` - Get similar events to a specific event
- **GET** `/api/events/upcoming` - Get upcoming events
- **GET** `/api/events/featured` - Get featured events
- **GET** `/api/events/search?q=query` - Search events
- **GET** `/api/events/search-suggestions?q=query` - Get search suggestions
- **GET** `/api/events/categories` - Get all event categories
- **GET** `/api/events/categories/{slug}` - Get single category details
- **GET** `/api/events?filter[city]=Nairobi` - Filter by city
- **GET** `/api/events?filter[category]=sports` - Filter by category slug/name
- **GET** `/api/events?filter[price_min]=1000&filter[price_max]=5000` - Filter by price range
- **GET** `/api/events?filter[date_after]=2025-01-01` - Filter by date

### Bookings
- **POST** `/api/bookings` - Create booking
- **GET** `/api/bookings` - User's bookings
- **GET** `/api/bookings/{id}` - Single booking details

### Tickets
- **GET** `/api/tickets` - User's tickets
- **GET** `/api/tickets/{id}` - Single ticket with QR code

### Categories
- **GET** `/api/events/categories` - List all categories with hierarchy
- **GET** `/api/events/categories/{slug}` - Get single category with subcategories

#### Categories Response Example
```json
{
  "status": "success",
  "data": {
    "categories": [
      {
        "id": "uuid",
        "name": "Events",
        "slug": "events",
        "icon_url": "url",
        "color_hex": "#FF5722",
        "description": "All types of events",
        "subcategories": [
          {
            "id": "uuid",
            "name": "Concerts",
            "slug": "concerts",
            "icon_url": "url",
            "color_hex": "#E91E63"
          }
        ]
      }
    ]
  }
}
```

#### Category Structure
The platform has 5 main parent categories:

1. **Events** (`events`)
   - Concerts (`concerts`)
   - Festivals (`festivals`)
   - Comedy Shows (`comedy-shows`)
   - Theater & Plays (`theater-plays`)
   - Conferences & Workshops (`conferences-workshops`)

2. **Sports** (`sports`)
   - Football (`football`)
   - Basketball (`basketball`)
   - Rugby (`rugby`)
   - Motorsports (`motorsports`) - F1, Rally, NASCAR, etc.
   - Pool (`pool`) - Billiards, Snooker, 8-ball, 9-ball
   - Combat (`combat`) - Boxing, MMA, UFC, Wrestling, Martial Arts

3. **Cinema** (`cinema`)
   - *No subcategories* - movies are listed directly under Cinema

4. **Experiences** (`experiences`)
   - Nightlife (`nightlife`)
   - Wellness (`wellness`)
   - Adventure (`adventure`)
   - Art Exhibitions (`art-exhibitions`)

5. **Stays** (`stays`)
   - Airbnb (`airbnb`)
   - Resorts (`resorts`)

## Headers Required

For authenticated requests:
```dart
headers: {
  'Accept': 'application/json',
  'Content-Type': 'application/json',
  'Authorization': 'Bearer $accessToken',
}
```

For public endpoints:
```dart
headers: {
  'Accept': 'application/json',
  'Content-Type': 'application/json',
}
```

## Test Credentials

### Test User Account (Created for you)
- Email: `test@test.com`
- Password: `password123`

### Other Available Test Accounts
- Email: `test@example.com` (Test User)
- Email: `organizer@example.com` (Test Organizer)
- Email: `kim@gmail.com` (tony yang)

⚠️ **Note**: The password for other accounts may need to be reset if unknown.

## Common Issues & Solutions

### Connection Refused
- Make sure the desktop Laravel server is running with `--host=0.0.0.0`
- Check firewall isn't blocking port 8000
- Verify both devices are on the same network

### CORS Errors
- Already configured to allow all origins
- If issues persist, check browser console for specific CORS error

### Slow Performance
- The desktop might be slow - consider these optimizations:
  1. Run `php artisan optimize`
  2. Use database indexes (already configured)
  3. Enable OPcache in PHP
  4. Consider using Laravel Octane for better performance

### Network Changes
If the desktop IP changes, update:
1. This file with new IP
2. Flutter app's baseUrl
3. Laravel's APP_URL in .env

## Performance Tips for Your Setup

Since your desktop is slow, here are quick wins:

1. **Use API response caching**:
   - Events list is cached for 5 minutes
   - Categories cached for 1 hour

2. **Optimize queries**:
   - Use pagination (already implemented)
   - Request only needed fields

3. **For Flutter app**:
   - Cache images locally
   - Implement pull-to-refresh instead of auto-refresh
   - Use lazy loading for lists

## New Event Endpoints Details

### Trending Events (with Category Filtering)
- **GET** `/api/events/trending`
- **Query Parameters**: 
  - `category` (optional) - Filter by category slug (e.g., 'sports', 'concerts', 'cinema')
  - `city` (optional) - Filter by city name
- **Response**:
```json
{
  "status": "success",
  "data": {
    "events": [
      {
        "id": "uuid",
        "title": "Event Name",
        "slug": "event-slug",
        "event_date": "2025-02-15T18:00:00",
        "venue_name": "Venue",
        "city": "Nairobi",
        "min_price": 1000,
        "max_price": 5000,
        "currency": "KES",
        "cover_image_url": "url",
        "featured": true,
        "capacity": 500,
        "tickets_sold": 350,
        "is_selling_fast": true,
        "sold_percentage": 70,
        "organizer": {...},
        "category": {...}
      }
    ],
    "meta": {
      "total": 20,
      "cache_expires_in": 3600,
      "filters": {
        "category": "sports",
        "city": "Nairobi"
      }
    }
  }
}
```
- **Notes**: 
  - Results are cached for 1 hour (separate cache per category/city combination)
  - Trending score based on: views (30%), tickets sold (50%), featured status, and event proximity
  - `is_selling_fast` is true when >70% tickets sold
  - Category filter supports both parent categories (e.g., 'events') and child categories (e.g., 'concerts')
  - When filtering by parent category, all child category events are included

### Similar Events
- **GET** `/api/events/{id}/similar`
- **Path Parameters**: 
  - `id` - Event UUID
- **Response**:
```json
{
  "status": "success",
  "data": {
    "events": [
      {
        "id": "uuid",
        "title": "Similar Event",
        "event_date": "2025-02-20T19:00:00",
        "venue_name": "Venue",
        "city": "Nairobi",
        "min_price": 1500,
        "currency": "KES",
        "cover_image_url": "url",
        "similarity_reasons": ["same_category", "same_city"],
        "organizer": {...},
        "category": {...}
      }
    ],
    "meta": {
      "total": 10,
      "base_event": {
        "id": "original-uuid",
        "title": "Original Event",
        "category": "Concerts"
      }
    }
  }
}
```
- **Notes**: 
  - Returns up to 10 similar events
  - Similarity based on: same category (priority 1), same city (priority 2), same organizer (priority 3)
  - `similarity_reasons` array explains why event is similar

### Search Suggestions
- **GET** `/api/events/search-suggestions?q=query`
- **Query Parameters**: 
  - `q` - Search query (min 2 characters)
- **Response**:
```json
{
  "status": "success",
  "data": {
    "suggestions": [
      {
        "type": "category_suggestion",
        "text": "Browse all Festivals",
        "category": "festivals",
        "action": "filter_category"
      },
      {
        "type": "event",
        "text": "Luo Festival 2025",
        "subtitle": "Feb 15 in Kisumu",
        "event_id": "uuid",
        "action": "view_event"
      },
      {
        "type": "location",
        "text": "Nairobi",
        "subtitle": "45 upcoming events",
        "action": "filter_city"
      },
      {
        "type": "organizer",
        "text": "Amazing Events Ltd",
        "subtitle": "12 events",
        "organizer_id": "uuid",
        "action": "filter_organizer"
      }
    ]
  }
}
```
- **Suggestion Types**:
  - `category_suggestion` - Smart category recommendations
  - `category` - Direct category matches
  - `event` - Specific event matches
  - `location` - City/location matches
  - `organizer` - Organizer matches
- **Actions** (for mobile app routing):
  - `filter_category` - Apply category filter
  - `view_event` - Navigate to event details
  - `filter_city` - Apply city filter
  - `filter_organizer` - Show organizer's events

## Alternative if Desktop is Too Slow

If the desktop remains too slow, consider:
1. **ngrok** (temporary public URL):
   ```bash
   ngrok http 8000
   ```
   Then use the ngrok URL in Flutter

2. **Move API to laptop**:
   - Clone the repo to laptop
   - Set up database
   - Both API and Flutter on same machine = fastest

## API Controller Architecture

The API has been refactored into smaller, focused controllers for maintainability:

### Controller Responsibilities
- **EventController** (~200 lines) - Core event operations
  - `index()` - List events with filters
  - `show()` - Get single event details
  - `upcoming()` - Get upcoming events
  - `featured()` - Get featured events

- **EventSearchController** (~300 lines) - Search and suggestions
  - `search()` - Smart search with brand mappings
  - `suggestions()` - Search suggestions with categories

- **EventTrendingController** (~280 lines) - Trending and similar events
  - `trending()` - Get trending events with category/city filters
  - `similar()` - Get similar events based on category/city/organizer

- **EventCategoryController** (~125 lines) - Category operations
  - `index()` - Get all categories with hierarchy
  - `show()` - Get single category details

This modular structure ensures:
- Single responsibility per controller
- Easier testing and maintenance
- Better code review acceptance
- Clear separation of concerns

## Questions?
Check the API documentation at:
- Scribe Docs: `http://192.168.0.103:8000/docs`
- Or generate fresh docs: `php artisan scribe:generate`