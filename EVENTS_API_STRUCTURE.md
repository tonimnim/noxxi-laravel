# EVENTS API RESPONSE STRUCTURES

## 1. GET /api/events - List All Events

### Available Query Parameters

#### Filters (`filter[key]=value`)
- `filter[status]` - Exact match: published, draft, cancelled
- `filter[city]` - Exact match: Nairobi, Lagos, etc.
- `filter[category_id]` - UUID of category
- `filter[category]` - Category slug or name (searches parent and child categories)
- `filter[upcoming]` - Scope for upcoming events only
- `filter[featured]` - Scope for featured events only
- `filter[date_after]` - Events after date (YYYY-MM-DD)
- `filter[date_before]` - Events before date (YYYY-MM-DD)
- `filter[price_min]` - Minimum price
- `filter[price_max]` - Maximum price
- `filter[title]` - Partial match in title

#### Sorting (`sort=field`)
- `sort=event_date` - Sort by event date ascending
- `sort=-event_date` - Sort by event date descending (default)
- `sort=created_at` - Sort by creation date
- `sort=min_price` - Sort by minimum price
- `sort=title` - Sort alphabetically by title

#### Includes (`include=relation`)
- `include=organizer` - Include organizer details
- `include=category` - Include category details
- `include=organizer,category` - Include both

#### Pagination
- `page=2` - Page number
- `per_page=20` - Items per page (default: 20)

### Response Structure
```json
{
  "status": "success",
  "data": {
    "events": [
      {
        "id": "uuid",
        "title": "Jazz Night at The Alchemist",
        "slug": "jazz-night-at-the-alchemist",
        "event_date": "2025-02-15T19:00:00Z",
        "end_date": null,
        "venue_name": "The Alchemist Bar",
        "city": "Nairobi",
        "min_price": 1500.00,
        "max_price": 5000.00,
        "currency": "KES",
        "cover_image_url": "https://example.com/image.jpg",
        "featured": false,
        "capacity": 500,
        "tickets_sold": 234,
        "organizer_id": "uuid",
        "category_id": "uuid",
        "organizer": {
          "id": "uuid",
          "business_name": "Alchemist Entertainment",
          "business_logo_url": "https://example.com/logo.jpg"
        },
        "category": {
          "id": "uuid",
          "name": "Music",
          "slug": "music"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 20,
      "total": 98
    }
  }
}
```

## 2. GET /api/events/{id} - Single Event Details

### Response Structure
```json
{
  "status": "success",
  "data": {
    "event": {
      "id": "uuid",
      "title": "Jazz Night at The Alchemist",
      "slug": "jazz-night-at-the-alchemist",
      "description": "Full HTML description of the event...",
      "event_date": "2025-02-15T19:00:00Z",
      "end_date": null,
      "venue_name": "The Alchemist Bar",
      "venue_address": "Westlands, Nairobi",
      "latitude": -1.2695,
      "longitude": 36.8098,
      "city": "Nairobi",
      "capacity": 500,
      "tickets_sold": 234,
      "available_tickets": 266,
      "min_price": 1500.00,
      "max_price": 5000.00,
      "currency": "KES",
      "ticket_types": [
        {
          "name": "Regular",
          "description": "General admission",
          "price": 1500.00,
          "quantity": 300,
          "sold": 150,
          "max_per_order": 10,
          "sale_start_date": "2025-01-01T00:00:00Z",
          "sale_end_date": "2025-02-15T18:00:00Z"
        },
        {
          "name": "VIP",
          "description": "VIP access with complimentary drinks",
          "price": 5000.00,
          "quantity": 50,
          "sold": 30,
          "max_per_order": 5,
          "sale_start_date": "2025-01-01T00:00:00Z",
          "sale_end_date": "2025-02-15T18:00:00Z"
        }
      ],
      "images": [
        "https://example.com/image1.jpg",
        "https://example.com/image2.jpg"
      ],
      "cover_image_url": "https://example.com/cover.jpg",
      "tags": ["jazz", "live music", "nightlife"],
      "status": "published",
      "featured": false,
      "age_restriction": 18,
      "terms_conditions": "Event specific terms...",
      "organizer": {
        "id": "uuid",
        "business_name": "Alchemist Entertainment",
        "business_logo_url": "https://example.com/logo.jpg",
        "business_description": "Premium entertainment venue..."
      },
      "category": {
        "id": "uuid",
        "name": "Music",
        "slug": "music"
      },
      "is_sold_out": false,
      "is_upcoming": true
    }
  }
}
```

## 3. GET /api/events/featured - Featured Events

### Response Structure
```json
{
  "status": "success",
  "data": {
    "events": [
      {
        "id": "uuid",
        "title": "Blankets and Wine",
        "slug": "blankets-and-wine",
        "event_date": "2025-02-20T12:00:00Z",
        "end_date": "2025-02-20T22:00:00Z",
        "venue_name": "Ngong Racecourse",
        "city": "Nairobi",
        "min_price": 3500.00,
        "max_price": 8000.00,
        "currency": "KES",
        "cover_image_url": "https://example.com/image.jpg",
        "featured": true,
        "featured_until": "2025-02-28T23:59:59Z",
        "capacity": 5000,
        "tickets_sold": 3421,
        "organizer_id": "uuid",
        "category_id": "uuid",
        "organizer": {
          "id": "uuid",
          "business_name": "House of DJs",
          "business_logo_url": "https://example.com/logo.jpg"
        }
      }
    ]
  }
}
```
Note: Returns maximum 10 featured events, sorted by event date

## 4. GET /api/events/upcoming - Upcoming Events

### Response Structure
Same as `/api/events` but automatically filtered for:
- Status = published
- Event date >= current date (or end_date >= current date for multi-day events)
- Sorted by event_date ascending

## 5. GET /api/categories - Event Categories

### Response Structure
```json
{
  "status": "success",
  "data": {
    "categories": [
      {
        "id": "uuid",
        "name": "Events",
        "slug": "events",
        "icon_url": "https://example.com/icon.svg",
        "color_hex": "#FF5733",
        "description": "Concerts, festivals, and social gatherings",
        "subcategories": [
          {
            "id": "uuid",
            "name": "Music",
            "slug": "music",
            "icon_url": "https://example.com/music-icon.svg",
            "color_hex": "#4A90E2"
          },
          {
            "id": "uuid",
            "name": "Comedy",
            "slug": "comedy",
            "icon_url": "https://example.com/comedy-icon.svg",
            "color_hex": "#7B68EE"
          },
          {
            "id": "uuid",
            "name": "Festivals",
            "slug": "festivals",
            "icon_url": "https://example.com/festival-icon.svg",
            "color_hex": "#2ECC71"
          }
        ]
      },
      {
        "id": "uuid",
        "name": "Sports",
        "slug": "sports",
        "icon_url": "https://example.com/sports-icon.svg",
        "color_hex": "#E74C3C",
        "description": "Sporting events and competitions",
        "subcategories": [
          {
            "id": "uuid",
            "name": "Football",
            "slug": "football",
            "icon_url": "https://example.com/football-icon.svg",
            "color_hex": "#27AE60"
          },
          {
            "id": "uuid",
            "name": "Rugby",
            "slug": "rugby",
            "icon_url": "https://example.com/rugby-icon.svg",
            "color_hex": "#8E44AD"
          }
        ]
      },
      {
        "id": "uuid",
        "name": "Cinema",
        "slug": "cinema",
        "icon_url": "https://example.com/cinema-icon.svg",
        "color_hex": "#34495E",
        "description": "Movies and film screenings",
        "subcategories": []
      },
      {
        "id": "uuid",
        "name": "Experiences",
        "slug": "experiences",
        "icon_url": "https://example.com/experiences-icon.svg",
        "color_hex": "#F39C12",
        "description": "Tours, workshops, and unique activities",
        "subcategories": [
          {
            "id": "uuid",
            "name": "Tours",
            "slug": "tours",
            "icon_url": "https://example.com/tours-icon.svg",
            "color_hex": "#16A085"
          },
          {
            "id": "uuid",
            "name": "Workshops",
            "slug": "workshops",
            "icon_url": "https://example.com/workshop-icon.svg",
            "color_hex": "#D35400"
          }
        ]
      }
    ]
  }
}
```

## 6. GET /api/categories/{slug} - Single Category Details

### Response Structure
```json
{
  "status": "success",
  "data": {
    "category": {
      "id": "uuid",
      "name": "Music",
      "slug": "music",
      "icon_url": "https://example.com/music-icon.svg",
      "color_hex": "#4A90E2",
      "description": "Live music performances and concerts",
      "is_parent": false,
      "parent": {
        "id": "uuid",
        "name": "Events",
        "slug": "events"
      }
    }
  }
}
```

## 7. Category-Specific Event Endpoints

### GET /api/events/experiences
Returns events in the "experiences" category

### GET /api/events/sports
Returns events in the "sports" category

### GET /api/events/cinema
Returns events in the "cinema" category

All return the same structure as `/api/events` but pre-filtered by category.

## Example Filter Combinations

### Find music events in Nairobi under 5000 KES
```
GET /api/events?filter[city]=Nairobi&filter[category]=music&filter[price_max]=5000
```

### Get featured upcoming events with organizer details
```
GET /api/events?filter[featured]=1&filter[upcoming]=1&include=organizer
```

### Search for "jazz" events sorted by price
```
GET /api/events?filter[title]=jazz&sort=min_price
```

### Get events in next 7 days
```
GET /api/events?filter[date_after]=2025-01-06&filter[date_before]=2025-01-13
```

## Notes

1. **Default Behavior**: 
   - Only published events are shown
   - Only upcoming events (not past) are shown
   - Default sort is by event_date descending
   - Default pagination is 20 items per page

2. **Category Filtering**:
   - Can filter by category_id (UUID) for exact match
   - Can filter by category slug/name which searches both parent and child categories

3. **Featured Events**:
   - Limited to 10 results
   - Only shows events where featured=true and featured_until > now()

4. **Multi-day Events**:
   - Events with end_date are shown if end_date hasn't passed
   - Events without end_date are shown if event_date hasn't passed

5. **Available Tickets**:
   - Calculated as: capacity - tickets_sold
   - is_sold_out: true when tickets_sold >= capacity