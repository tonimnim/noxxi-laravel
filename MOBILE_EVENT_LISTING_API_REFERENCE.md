# Mobile App Event/Listing API Reference

## Main Endpoints

### 1. Get Single Event Details
**Endpoint:** `GET /api/events/{id}`

This endpoint returns COMPLETE event details for displaying a full event page.

```json
{
  "status": "success",
  "data": {
    "event": {
      // Basic Information
      "id": "uuid-of-event",
      "title": "Jazz Night at Nairobi Theatre",
      "slug": "jazz-night-nairobi-theatre",
      "description": "Full HTML/text description of the event...",
      
      // Date & Time
      "event_date": "2025-02-15T19:00:00Z",
      "end_date": "2025-02-15T23:00:00Z",  // null if single-day event
      
      // Venue Information
      "venue_name": "Nairobi National Theatre",
      "venue_address": "Harry Thuku Road, Nairobi",
      "city": "Nairobi",
      "latitude": -1.2921,
      "longitude": 36.8219,
      
      // Capacity & Availability
      "capacity": 500,
      "tickets_sold": 245,
      "available_tickets": 255,
      "is_sold_out": false,
      "is_upcoming": true,
      
      // Pricing
      "min_price": 1500.00,
      "max_price": 5000.00,
      "currency": "KES",
      
      // Ticket Types (IMPORTANT for booking)
      "ticket_types": [
        {
          "name": "Regular",
          "price": 1500.00,
          "description": "General admission",
          "available": 180,
          "max_per_booking": 10,
          "benefits": ["Entry to event"]
        },
        {
          "name": "VIP",
          "price": 5000.00,
          "description": "VIP experience with front row seats",
          "available": 75,
          "max_per_booking": 5,
          "benefits": ["Front row seats", "Complimentary drinks", "Meet & greet"]
        }
      ],
      
      // Media
      "cover_image_url": "https://res.cloudinary.com/noxxi/image/upload/event_123.jpg",
      "images": [
        "https://res.cloudinary.com/noxxi/image/upload/gallery1.jpg",
        "https://res.cloudinary.com/noxxi/image/upload/gallery2.jpg"
      ],
      
      // Metadata
      "tags": ["Jazz", "Live Music", "Weekend"],
      "status": "published",
      "featured": true,
      "age_restriction": 18,  // Minimum age, null if no restriction
      
      // Policies
      "terms_conditions": "Full terms and conditions text...",
      
      // Organizer Information
      "organizer": {
        "id": "organizer-uuid",
        "business_name": "Elite Entertainment",
        "business_email": "contact@elite.com",
        "business_logo_url": "https://res.cloudinary.com/noxxi/logo.png",
        "business_description": "Premier event organizers in Kenya..."
      },
      
      // Category
      "category": {
        "id": "category-uuid",
        "name": "Music & Concerts",
        "slug": "music-concerts"
      }
    }
  }
}
```

### 2. List Events (Homepage/Search)
**Endpoint:** `GET /api/events`

Returns a paginated list with ESSENTIAL fields for listing pages.

```json
{
  "status": "success",
  "data": {
    "events": [
      {
        // Essential Fields for List View
        "id": "event-uuid",
        "title": "Jazz Night at Nairobi Theatre",
        "slug": "jazz-night-nairobi-theatre",
        "event_date": "2025-02-15T19:00:00Z",
        "end_date": null,
        
        // Location
        "venue_name": "Nairobi National Theatre",
        "city": "Nairobi",
        
        // Pricing
        "min_price": 1500.00,
        "max_price": 5000.00,
        "currency": "KES",
        
        // Media
        "cover_image_url": "https://res.cloudinary.com/noxxi/image.jpg",
        
        // Status
        "featured": true,
        "capacity": 500,
        "tickets_sold": 245,
        
        // Related Data
        "organizer_id": "organizer-uuid",
        "category_id": "category-uuid",
        
        // Loaded Relations
        "organizer": {
          "id": "organizer-uuid",
          "business_name": "Elite Entertainment",
          "business_logo_url": "https://res.cloudinary.com/logo.png"
        },
        "category": {
          "id": "category-uuid",
          "name": "Music & Concerts",
          "slug": "music-concerts"
        }
      }
      // ... more events
    ],
    "meta": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 20,
      "total": 195
    }
  }
}
```

### 3. Featured Events
**Endpoint:** `GET /api/events/featured`

Returns top 10 featured events with additional `featured_until` field.

```json
{
  "status": "success",
  "data": {
    "events": [
      {
        // Same fields as list view PLUS:
        "featured_until": "2025-02-01T00:00:00Z"
        // ... all other list fields
      }
    ]
  }
}
```

### 4. Upcoming Events
**Endpoint:** `GET /api/events/upcoming`

Same structure as list events, but filtered to show only future events.

### 5. Category-Specific Events
**Endpoints:**
- `GET /api/experiences` - Experience events
- `GET /api/sports` - Sports events  
- `GET /api/cinema` - Cinema events

Same structure as list events, pre-filtered by category.

## Query Parameters

### Filtering
```
GET /api/events?filter[city]=Nairobi
GET /api/events?filter[category]=music-concerts
GET /api/events?filter[price_min]=1000&filter[price_max]=5000
GET /api/events?filter[date_after]=2025-02-01
GET /api/events?filter[date_before]=2025-03-01
GET /api/events?filter[title]=jazz  // Partial match
```

### Sorting
```
GET /api/events?sort=event_date  // Ascending
GET /api/events?sort=-event_date  // Descending
GET /api/events?sort=-created_at  // Newest first
GET /api/events?sort=min_price  // Cheapest first
```

### Pagination
```
GET /api/events?page=2&per_page=10
```

### Including Relations
```
GET /api/events?include=organizer,category
```

## Field Descriptions for Mobile Developers

### Critical Fields for Booking
- `id` - Use for creating bookings
- `ticket_types` - Array of available ticket types with names and prices
- `currency` - Display with all prices
- `available_tickets` - Check before allowing booking

### Display Fields
- `cover_image_url` - Main image for cards/headers
- `images` - Gallery images array
- `venue_name` & `venue_address` - Location display
- `latitude` & `longitude` - For map integration
- `event_date` & `end_date` - Format for local timezone

### Status Indicators
- `is_sold_out` - Show "SOLD OUT" badge
- `is_upcoming` - Hide if false (past event)
- `featured` - Show "FEATURED" badge
- `tickets_sold` vs `capacity` - Progress bar

### Rich Content
- `description` - May contain HTML, sanitize for display
- `tags` - For categorization/chips
- `age_restriction` - Show age requirement if not null
- `terms_conditions` - Show in expandable section

## Mobile Implementation Tips

1. **List View Optimization**
   - Use list endpoint fields only
   - Load full details on tap via show endpoint
   - Cache cover images

2. **Booking Flow**
   - Get fresh event details before booking
   - Use exact `ticket_types.name` from response
   - Never calculate prices client-side

3. **Currency Display**
   ```dart
   String formatPrice(double price, String currency) {
     final symbols = {
       'KES': 'KSh',
       'NGN': '₦',
       'ZAR': 'R',
       'GHS': 'GH₵',
       'USD': '$'
     };
     return '${symbols[currency] ?? currency} ${price.toStringAsFixed(2)}';
   }
   ```

4. **Date Handling**
   - All dates are in UTC
   - Convert to local timezone for display
   - Show "Today", "Tomorrow" for near dates

5. **Sold Out Logic**
   ```dart
   if (event.isSoldOut || event.availableTickets == 0) {
     showSoldOutBadge();
     disableBookingButton();
   }
   ```

6. **Image Optimization**
   - Cloudinary URLs support transformations
   - Add `/w_400,h_300,c_fill/` for thumbnails
   - Example: `https://res.cloudinary.com/.../w_400,h_300,c_fill/event_123.jpg`

## Error Handling

```json
// Event not found
{
  "status": "error",
  "message": "Event not found",
  "code": 404
}

// Event not available (draft/cancelled)
{
  "status": "error",
  "message": "This event is not available",
  "code": 403
}
```

## Complete Field Reference

| Field | Type | List View | Detail View | Description |
|-------|------|-----------|-------------|-------------|
| id | UUID | ✓ | ✓ | Event unique identifier |
| title | String | ✓ | ✓ | Event name |
| slug | String | ✓ | ✓ | URL-friendly name |
| description | Text | ✗ | ✓ | Full event description |
| event_date | DateTime | ✓ | ✓ | Start date/time |
| end_date | DateTime? | ✓ | ✓ | End date/time (multi-day) |
| venue_name | String | ✓ | ✓ | Venue/location name |
| venue_address | String | ✗ | ✓ | Full address |
| city | String | ✓ | ✓ | City name |
| latitude | Float | ✗ | ✓ | GPS latitude |
| longitude | Float | ✗ | ✓ | GPS longitude |
| capacity | Integer | ✓ | ✓ | Total capacity |
| tickets_sold | Integer | ✓ | ✓ | Tickets sold count |
| available_tickets | Integer | ✗ | ✓ | Remaining tickets |
| min_price | Decimal | ✓ | ✓ | Lowest ticket price |
| max_price | Decimal | ✓ | ✓ | Highest ticket price |
| currency | String | ✓ | ✓ | Price currency code |
| ticket_types | Array | ✗ | ✓ | Detailed ticket options |
| cover_image_url | String | ✓ | ✓ | Main event image |
| images | Array | ✗ | ✓ | Gallery images |
| tags | Array | ✗ | ✓ | Event tags |
| featured | Boolean | ✓ | ✓ | Is featured event |
| age_restriction | Integer? | ✗ | ✓ | Minimum age |
| terms_conditions | Text | ✗ | ✓ | Event T&Cs |
| organizer | Object | ✓ | ✓ | Organizer details |
| category | Object | ✓ | ✓ | Category details |
| is_sold_out | Boolean | ✗ | ✓ | Computed sold out status |
| is_upcoming | Boolean | ✗ | ✓ | Computed future status |

That's everything the mobile app receives for event/listing details!