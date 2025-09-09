# Simplified Booking & Payment Flow

## Architecture Overview
Direct booking and payment in one step. Flutter sends booking data directly with payment initialization. No cache, no prepare endpoint, no redundant calls.

## The Single Flow

### Flutter → Backend (One Call)
**Endpoint:** `POST /api/payments/initialize`  
**Purpose:** Validates, creates booking, and initiates payment in one atomic transaction

**Request:**
```json
{
  "event_id": "uuid",
  "ticket_types": [
    {
      "name": "General Admission",
      "quantity": 2
    }
  ],
  "payment_method": "card",  // or "mpesa" or "bank_transfer"
  "phone_number": "254700000000"  // Required only if payment_method is "mpesa"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "booking_id": "uuid",
    "transaction_id": "uuid",
    "payment_url": "https://checkout.paystack.com/...",
    "access_code": "xxx",
    "reference": "xxx",
    "amount": 400.00,  // User pays EXACTLY the ticket price (no added fees)
    "currency": "KES"
  }
}
```

## Key Points

### User Payment
- **User pays:** Exact ticket price (e.g., 400 KES)
- **NO service fees added to user**
- **NO processing fees added to user**
- What you see is what you pay

### Platform Revenue
- **Commission:** Deducted from organizer's payout (NOT from user)
- **Set by admin:** During organizer verification (no defaults)
- **Example:** User pays 400, organizer gets 360 (if 10% commission)

### Payment Processing
- **Paystack fees:** Between organizer and Paystack (we don't track)
- **One endpoint:** Handles card, M-Pesa, bank transfer
- **Atomic operation:** Booking only exists if payment initiates successfully

## Flutter Implementation

```dart
// Simple, direct payment with booking data
Future<PaymentResponse> initializePayment({
  required String eventId,
  required List<TicketSelection> tickets,
  required String paymentMethod,
  String? phoneNumber,  // Only for M-Pesa
}) async {
  final response = await apiClient.post('/api/payments/initialize', {
    'event_id': eventId,
    'ticket_types': tickets.map((t) => {
      'name': t.typeName,
      'quantity': t.quantity,
    }).toList(),
    'payment_method': paymentMethod,
    'phone_number': phoneNumber,
  });
  
  if (response['success']) {
    return PaymentResponse.fromJson(response['data']);
  }
  throw Exception(response['message']);
}
```

## Server-Side Process

1. **Receive request** with event ID and tickets
2. **Lock event** to prevent overselling
3. **Validate:**
   - Event is available (not cancelled/past)
   - Tickets are available
   - User has no duplicate bookings
4. **Calculate prices** server-side (never trust client)
5. **Create booking** with exact ticket price as total
6. **Track commission** separately (for organizer payout)
7. **Initialize payment** with Paystack
8. **Return payment URL** to Flutter

All in one database transaction - if anything fails, everything rolls back.

## Commission Model

```
Ticket Price: 400 KES (what user sees and pays)
Platform Commission: 10% (set by admin, not user-facing)
Organizer Receives: 360 KES (during payout)
Paystack Fees: Handled between organizer and Paystack
```

## What Was Removed

❌ **No prepare endpoint** - Direct payment only  
❌ **No cache management** - Flutter stores locally  
❌ **No service fees on user** - User pays exact price  
❌ **No default commission** - Admin must set  
❌ **No Paystack fee tracking** - Not our concern  
❌ **No separate M-Pesa endpoint** - One endpoint for all  

## Error Handling

- **Invalid tickets:** Returns validation error, no booking created
- **Payment fails:** Transaction rolled back, no booking in database
- **Retry:** User can retry immediately (no 30-minute blocking)
- **No commission set:** Organizer gets full amount (admin's responsibility)

## Testing Scenarios

1. **Normal flow:** Send data → Get URL → Pay → Success
2. **Sold out:** Send data → Get "sold out" error → No booking
3. **Payment failure:** Send data → Payment init fails → No booking
4. **Immediate retry:** Failed payment → Try again → Works

## Summary

- **One API call** creates booking and initiates payment
- **User pays exact price** (no hidden fees)
- **Commission from organizer** (not user)
- **Clean and simple** architecture