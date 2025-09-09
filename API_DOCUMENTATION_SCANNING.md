# NOXXI TICKET SCANNING & VALIDATION API DOCUMENTATION

## Overview
This document describes the complete ticket scanning and validation process for the Noxxi platform, including manager permissions, QR code validation, and check-in procedures.

## Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer {access_token}
```

## Manager/Scanner Management

### 1. Add Manager/Scanner
**Endpoint:** `POST /api/v1/managers`

**Description:** Allows organizers to add managers who can scan tickets for their events.

**Request Body:**
```json
{
  "search": "manager@example.com",  // Email or phone number
  "event_access": "all",             // "all" or "specific"
  "event_ids": [],                   // Required if event_access is "specific"
  "can_scan_tickets": true,
  "can_validate_entries": true,
  "valid_from": "2025-01-01",        // Optional
  "valid_until": "2025-12-31",       // Optional
  "notes": "Event manager for Jazz Night"  // Optional
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Manager added successfully",
  "data": {
    "manager": {
      "id": "uuid",
      "user": {
        "id": "uuid",
        "name": "John Doe",
        "email": "manager@example.com",
        "phone": "+254700000000"
      },
      "permissions": {
        "can_scan_tickets": true,
        "can_validate_entries": true
      },
      "event_access": "all",
      "event_ids": [],
      "is_active": true,
      "created_at": "2025-01-01T00:00:00Z"
    }
  }
}
```

### 2. List Managers
**Endpoint:** `GET /api/v1/managers`

**Query Parameters:**
- `is_active` (boolean): Filter by active status
- `per_page` (integer): Results per page (default: 20)

### 3. Update Manager Permissions
**Endpoint:** `PUT /api/v1/managers/{id}`

**Request Body:**
```json
{
  "event_access": "specific",
  "event_ids": ["event-uuid-1", "event-uuid-2"],
  "can_scan_tickets": true,
  "is_active": true
}
```

### 4. Remove Manager
**Endpoint:** `DELETE /api/v1/managers/{id}`

### 5. Get Scan Activity
**Endpoint:** `GET /api/v1/managers/activity`

**Query Parameters:**
- `manager_id` (uuid): Filter by specific manager
- `event_id` (uuid): Filter by event
- `date_from` (date): Start date
- `date_to` (date): End date

## Ticket Validation & Check-in

### 1. Validate Ticket (QR Code)
**Endpoint:** `POST /api/v1/tickets/validate`

**Description:** Validates a ticket QR code without checking it in. Used to verify ticket authenticity.

**Request Body:**
```json
{
  "qr_content": "base64_encoded_qr_data",
  "gate_id": "Gate A",      // Optional
  "device_id": "device-123"  // Optional
}
```

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "success": true,
    "message": "Ticket is valid",
    "ticket": {
      "id": "ticket-uuid",
      "code": "TKT-123456",
      "type": "VIP",
      "holder_name": "Jane Doe",
      "holder_email": "jane@example.com",
      "seat_number": "A12",
      "seat_section": "VIP"
    },
    "event": {
      "id": "event-uuid",
      "title": "Jazz Night",
      "date": "2025-02-15T19:00:00Z",
      "venue": "National Theatre"
    },
    "can_check_in": true
  }
}
```

**Error Response (Already Used):**
```json
{
  "status": "error",
  "message": "Ticket already used",
  "data": {
    "success": false,
    "used_at": "2025-02-15T19:30:00Z",
    "entry_gate": "Gate A"
  }
}
```

### 2. Check-in Ticket
**Endpoint:** `POST /api/v1/tickets/check-in`

**Description:** Marks a ticket as used and records the check-in.

**Request Body:**
```json
{
  "ticket_id": "ticket-uuid",
  "gate_id": "Gate A",      // Optional
  "device_id": "device-123"  // Optional
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "success": true,
    "message": "Ticket checked in successfully",
    "check_in_time": "2025-02-15T19:30:00Z"
  }
}
```

### 3. Batch Validate
**Endpoint:** `POST /api/v1/tickets/batch-validate`

**Description:** Validate multiple tickets at once (max 50).

**Request Body:**
```json
{
  "tickets": [
    {"qr_content": "qr_data_1"},
    {"qr_content": "qr_data_2"}
  ],
  "gate_id": "Gate A"
}
```

### 4. Manual Validation (Backup)
**Endpoint:** `POST /api/v1/tickets/validate-by-code`

**Description:** Validate ticket using ticket code instead of QR (backup method).

**Request Body:**
```json
{
  "ticket_code": "TKT-123456",
  "event_id": "event-uuid"
}
```

## Offline Mode Support

### Get Event Manifest
**Endpoint:** `GET /api/v1/events/{id}/manifest`

**Description:** Downloads all valid tickets for offline validation.

**Response:**
```json
{
  "status": "success",
  "data": {
    "event": {
      "id": "event-uuid",
      "title": "Jazz Night",
      "venue": "National Theatre",
      "date": "2025-02-15T19:00:00Z"
    },
    "manifest": {
      "tickets": [
        {
          "id": "ticket-uuid",
          "code": "TKT-123456",
          "signature": "hmac_signature",
          "type": "VIP",
          "holder_name": "Jane Doe"
        }
      ],
      "total_tickets": 500,
      "generated_at": "2025-02-15T12:00:00Z"
    },
    "expires_at": "2025-02-15T12:05:00Z"
  }
}
```

## Real-time Statistics

### Get Check-in Statistics
**Endpoint:** `GET /api/v1/events/{id}/check-in-stats`

**Response:**
```json
{
  "status": "success",
  "data": {
    "statistics": {
      "total_tickets": 500,
      "checked_in": 234,
      "not_checked_in": 266,
      "cancelled": 10,
      "transferred": 5,
      "check_in_percentage": 46.8
    },
    "recent_check_ins": [
      {
        "id": "ticket-uuid",
        "holder_name": "John Doe",
        "ticket_type": "Regular",
        "used_at": "2025-02-15T19:30:00Z",
        "entry_gate": "Gate A"
      }
    ],
    "gate_statistics": [
      {
        "entry_gate": "Gate A",
        "count": 120
      },
      {
        "entry_gate": "Gate B",
        "count": 114
      }
    ],
    "last_updated": "2025-02-15T19:35:00Z"
  }
}
```

## Cancelled Tickets Management

### Get Cancelled Tickets
**Endpoint:** `GET /api/v1/tickets/cancelled`

**Query Parameters:**
- `filter[event_id]`: Filter by event
- `filter[date_from]`: Cancellation date from
- `filter[date_to]`: Cancellation date to
- `sort`: Sort by cancelled_at, price, etc.

### Get Cancellation Statistics
**Endpoint:** `GET /api/v1/tickets/cancelled/stats`

**Query Parameters:**
- `event_id`: Filter by specific event
- `date_from`: Start date
- `date_to`: End date
- `group_by`: Group by day/week/month/event

### Bulk Cancel Tickets
**Endpoint:** `POST /api/v1/tickets/bulk-cancel`

**Request Body:**
```json
{
  "ticket_ids": ["ticket-uuid-1", "ticket-uuid-2"],
  "reason": "Event postponed"
}
```

## Permission System

### How Permissions Work

1. **Organizer Access**: Event organizers have full access to all their events' tickets.

2. **Manager Access**: Managers can be granted access to:
   - All events of an organizer (`event_access: "all"`)
   - Specific events only (`event_access: "specific"` with `event_ids`)

3. **Permission Types**:
   - `can_scan_tickets`: Allows scanning and validating tickets
   - `can_validate_entries`: Allows checking in attendees

4. **Time-based Access**: Managers can have time-limited access using:
   - `valid_from`: Start date of access
   - `valid_until`: End date of access

### Permission Check Flow

1. System checks if user is the event organizer
2. If not, checks if user is an active manager for the organizer
3. Verifies manager has `can_scan_tickets` permission
4. Checks if manager has access to the specific event
5. Validates time-based restrictions if set

## Flutter App Integration

### QR Code Scanning Flow

1. **Initialize Scanner**: App opens camera to scan QR code
2. **Decode QR**: Extract base64-encoded data from QR image
3. **Validate Ticket**: Call `/api/v1/tickets/validate` with QR content
4. **Display Result**: Show validation result to scanner
5. **Check-in Option**: If valid, offer to check-in the ticket
6. **Record Check-in**: Call `/api/v1/tickets/check-in` if confirmed

### Offline Mode

1. **Download Manifest**: Before event, download manifest using `/api/v1/events/{id}/manifest`
2. **Store Locally**: Cache manifest in secure storage
3. **Offline Validation**: Validate tickets against cached manifest using HMAC signatures
4. **Sync Later**: When online, sync check-in data back to server

### Error Handling

Handle these common scenarios:
- Network connectivity issues
- Invalid/expired QR codes
- Already used tickets
- Permission denied errors
- Rate limiting (429 errors)

### Best Practices

1. **Cache QR validation results** for 30 seconds to prevent double-scanning
2. **Use device_id** to track which device performed the scan
3. **Implement retry logic** for network failures
4. **Show clear feedback** for successful/failed scans
5. **Log scan attempts** locally for debugging
6. **Respect rate limits**: 
   - Validation: 30 requests/minute
   - Check-in: 60 requests/minute
   - Manifest: 5 requests/minute

## Security Considerations

1. **QR Code Security**:
   - QR codes contain HMAC-SHA256 signatures
   - Signatures expire after 24 hours
   - Each QR request generates a new signature

2. **Permission Verification**:
   - All endpoints verify user permissions
   - Manager permissions are checked in real-time
   - Time-based restrictions are enforced

3. **Audit Trail**:
   - All check-ins are logged with scanner ID
   - Activity tracking for compliance
   - Device tracking for security

4. **Rate Limiting**:
   - Prevents brute force attempts
   - Protects against DoS attacks
   - Ensures fair usage

## Testing

Use these test credentials in development:
- Organizer: anto@gmail.com / 8800kl
- Test OTP: 123456 (development only)

Test endpoints with tools like Postman or curl:
```bash
# Validate ticket
curl -X POST https://api.noxxi.com/api/v1/tickets/validate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"qr_content": "base64_qr_data"}'

# Add manager
curl -X POST https://api.noxxi.com/api/v1/managers \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"search": "manager@example.com", "event_access": "all"}'
```