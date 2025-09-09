# Mobile App Payment Implementation Guide

## Overview
Payment flow in Noxxi: Select Event → Choose Tickets → Create Booking → Initialize Payment → Process Payment → Receive Tickets

## Complete Payment Flow

### Step 1: Create Booking
First, create a booking to reserve tickets. Server calculates all prices.

```dart
POST /api/bookings
Headers: {
  "Authorization": "Bearer {access_token}",
  "Content-Type": "application/json"
}
Body: {
  "event_id": "uuid-of-event",
  "ticket_types": [
    {
      "name": "Regular",      // Must match event's ticket type name
      "quantity": 2
    },
    {
      "name": "VIP",
      "quantity": 1  
    }
  ],
  "customer_details": {
    "phone": "+254712345678"  // Optional
  }
}

Response: {
  "success": true,
  "message": "Booking created successfully",
  "data": {
    "booking_id": "uuid-of-booking",
    "booking_reference": "BK12345678",
    "amount": 5000.00,           // Total amount to pay
    "currency": "KES",            // Event's currency
    "expires_at": "2025-01-03T12:30:00Z",  // 30 minutes to pay
    "payment_options": ["card", "mpesa"],
    "booking": {
      // Full booking details including event info
    }
  }
}
```

**IMPORTANT**: 
- Server calculates ALL prices - never trust client prices
- Booking expires in 30 minutes if not paid
- `ticket_types.name` MUST match exactly with event's ticket configuration

### Step 2A: Initialize Card Payment (Paystack)
```dart
POST /api/payments/paystack/initialize
Headers: {
  "Authorization": "Bearer {access_token}",
  "Content-Type": "application/json"
}
Body: {
  "booking_id": "uuid-from-step-1",
  "payment_method": "card"  // or "bank_transfer"
}

Response: {
  "success": true,
  "data": {
    "payment_url": "https://checkout.paystack.com/xyz123",
    "access_code": "xyz123",
    "reference": "PAY_ABC123_1234567890",
    "transaction_id": "uuid-of-transaction"
  }
}
```

**Mobile Action**:
1. Open WebView with `payment_url`
2. User completes payment on Paystack
3. Listen for redirect or use Step 3 to verify

### Step 2B: Initialize M-Pesa Payment (Kenya)
```dart
POST /api/payments/mpesa/initialize
Headers: {
  "Authorization": "Bearer {access_token}",
  "Content-Type": "application/json"
}
Body: {
  "booking_id": "uuid-from-step-1",
  "phone_number": "254712345678"  // Must be Safaricom number, no + sign
}

Response: {
  "success": true,
  "data": {
    "message": "STK Push sent to 254712345678",
    "transaction_id": "uuid-of-transaction",
    "reference": "PAY_MPE_1234567890"
  }
}
```

**Mobile Action**:
1. Show "Check your phone for M-Pesa prompt"
2. User enters PIN on their phone
3. Poll Step 3 to verify payment status

### Step 3: Verify Payment Status
Poll this endpoint to check if payment is complete:

```dart
GET /api/payments/verify/{transaction_id}
Headers: {
  "Authorization": "Bearer {access_token}"
}

Response (Pending): {
  "success": true,
  "data": {
    "transaction_id": "uuid",
    "status": "pending",
    "is_completed": false,
    "is_failed": false
  }
}

Response (Success): {
  "success": true,
  "data": {
    "transaction_id": "uuid",
    "status": "completed",
    "amount": 5000.00,
    "currency": "KES",
    "payment_gateway": "paystack",
    "payment_reference": "PAY_ABC123_1234567890",
    "booking_id": "uuid-of-booking",
    "is_completed": true,
    "is_failed": false
  }
}

Response (Failed): {
  "success": true,
  "data": {
    "transaction_id": "uuid",
    "status": "failed",
    "is_completed": false,
    "is_failed": true,
    "failure_reason": "Insufficient funds"
  }
}
```

**Polling Strategy**:
- Poll every 3 seconds for first 30 seconds
- Then every 5 seconds for next minute
- Then every 10 seconds until timeout (5 minutes total)
- Stop polling if `is_completed` or `is_failed` is true

### Step 4: Get Tickets After Payment
Once payment is verified as complete:

```dart
GET /api/bookings/{booking_id}/tickets
Headers: {
  "Authorization": "Bearer {access_token}"
}

Response: {
  "success": true,
  "data": {
    "tickets": [
      {
        "id": "ticket-uuid-1",
        "ticket_number": "TKT123456",
        "ticket_type": "Regular",
        "attendee_name": "John Doe",
        "status": "valid",
        "qr_code": "base64-encoded-qr-data",
        "event": {
          "title": "Jazz Night",
          "event_date": "2025-02-15",
          "venue_name": "Nairobi Theatre"
        }
      },
      // ... more tickets
    ]
  }
}
```

## Currency Handling

The platform supports multiple African currencies. Amount conversions are handled server-side:

| Currency | Code | Example Amount | Paystack Format |
|----------|------|----------------|-----------------|
| Nigerian Naira | NGN | 5000.00 | 500000 kobo |
| Kenyan Shilling | KES | 5000.00 | 500000 cents |
| South African Rand | ZAR | 500.00 | 50000 cents |
| Ghanaian Cedi | GHS | 100.00 | 10000 pesewas |
| US Dollar | USD | 50.00 | 5000 cents |

**IMPORTANT**: Server handles all currency conversions. Mobile app should display amounts as received.

## Error Handling

### Common Error Responses

```dart
// Booking expired
{
  "success": false,
  "message": "Booking has expired",
  "code": 400
}

// Payment already processed
{
  "success": false,
  "message": "Payment already completed for this booking",
  "code": 400
}

// Invalid ticket type
{
  "success": false,
  "message": "Invalid ticket selection",
  "errors": {
    "VIP": ["Sold out"],
    "Regular": ["Maximum 10 tickets per booking"]
  }
}
```

## WebView Integration for Paystack

```dart
// Flutter WebView example
WebView(
  initialUrl: paymentUrl,
  javascriptMode: JavascriptMode.unrestricted,
  navigationDelegate: (NavigationRequest request) {
    // Check for callback URL
    if (request.url.contains('payment/callback')) {
      // Extract reference from URL
      final uri = Uri.parse(request.url);
      final reference = uri.queryParameters['reference'];
      
      // Close WebView and verify payment
      Navigator.pop(context);
      verifyPayment(transactionId);
      
      return NavigationDecision.prevent;
    }
    return NavigationDecision.navigate;
  },
)
```

## Testing Payments

### Test Cards (Paystack)
```
Success: 4084084084084081
Failed: 4084080000000409
CVV: 408 (any 3 digits)
Expiry: Any future date
PIN: 0000
OTP: 123456
```

### Test M-Pesa (Development)
- Any valid Kenyan phone format: 254712345678
- Payment will auto-complete in dev environment
- Check Laravel logs for simulated responses

## Payment State Management

```dart
enum PaymentState {
  idle,
  creatingBooking,
  initializingPayment,
  awaitingPayment,    // Show WebView or STK push message
  verifyingPayment,   // Polling status
  paymentSuccessful,  // Show success, fetch tickets
  paymentFailed,      // Show error, allow retry
}
```

## Important Implementation Notes

1. **Never trust client-side prices** - Server calculates everything
2. **Always verify payment** - Don't assume success from WebView close
3. **Handle timeouts** - Bookings expire after 30 minutes
4. **Save transaction ID** - For support and refund requests
5. **Currency display** - Show currency symbol based on `currency` field
6. **Retry mechanism** - Allow users to retry failed payments with same booking
7. **Network failures** - Cache transaction_id locally for recovery

## Security Considerations

1. **SSL Pinning** - Implement for production
2. **Token Storage** - Use secure storage for access tokens
3. **WebView Security** - Only allow Paystack domains
4. **Amount Verification** - Always display server-provided amounts
5. **Reference Tracking** - Store payment references for audit

## Support & Debugging

When payment issues occur, provide these to support:
- Booking ID
- Transaction ID  
- Payment Reference
- Timestamp
- Error message

## Flow Diagram

```
User Selects Event
       ↓
Choose Ticket Types
       ↓
Create Booking (POST /api/bookings)
       ↓
Show Payment Options
       ↓
    ┌──────────────┬──────────────┐
    ↓              ↓              ↓
Card/Bank      M-Pesa      Bank Transfer
    ↓              ↓              ↓
Initialize     Initialize    Initialize
    ↓              ↓              ↓
WebView       STK Push      Bank Details
    ↓              ↓              ↓
    └──────────────┴──────────────┘
                   ↓
         Verify Payment (Poll)
                   ↓
            Payment Success?
                   ↓
         Yes ←─────┴─────→ No
          ↓                  ↓
    Get Tickets         Show Error
          ↓                  ↓
    Show Success        Allow Retry
```

That's the complete payment implementation. Simple, secure, and handles all African payment methods.