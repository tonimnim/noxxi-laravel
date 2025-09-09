# Mobile App Email Verification Architecture

## Overview
Users MUST verify their email before getting access tokens. No exceptions.

## Flow (Simple & Direct)

### 1. Registration
```dart
POST /api/auth/register
Body: {
  "full_name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}

Response: {
  "status": "success",
  "message": "Registration successful. Please verify your email to continue.",
  "data": {
    "user": {...},
    "requires_verification": true
    // NO TOKENS HERE!
  }
}
```

**Mobile Action:** 
- Store user email in local storage
- Navigate to OTP verification screen immediately
- Show message: "We've sent a 6-digit code to your email"

### 2. OTP Verification Screen
```dart
// Simple 6-digit input screen
POST /api/auth/verify-email
Body: {
  "email": "john@example.com",  // From local storage
  "code": "123456"               // User input
}

Success Response: {
  "status": "success", 
  "data": {
    "user": {...},
    "token": "eyJ0eXAiOiJKV1...",      // NOW you get tokens!
    "refresh_token": "80characterstring",
    "expires_at": "2125-01-01"          // 100 years (effectively never)
  }
}

Error Response: {
  "status": "error",
  "message": "Invalid verification code"
}
```

**Mobile Action on Success:**
- Store access token
- Store refresh token  
- Store user data
- Navigate to appropriate screen (home/organizer dashboard)

**Mobile Action on Error:**
- Show error message
- Clear OTP input fields
- Let user retry

### 3. Resend Code
```dart
POST /api/auth/resend-verification
Body: {
  "email": "john@example.com"  // From local storage
}

Response: {
  "status": "success",
  "message": "Verification code sent"
}
```

**Mobile Action:**
- Show success message
- Clear OTP fields
- Start 60-second cooldown before allowing another resend

### 4. Check Verification Status (Optional)
```dart
GET /api/auth/verification-status?email=john@example.com

Response: {
  "status": "success",
  "data": {
    "verified": false,
    "email": "john@example.com"
  }
}
```

## Important Notes for Mobile Developer

1. **NO TOKENS WITHOUT VERIFICATION**
   - Registration does NOT return tokens
   - Only `/api/auth/verify-email` returns tokens after successful verification
   - Don't try to access protected endpoints without verification

2. **TEST MODE**
   - In development, the OTP code is logged to Laravel logs
   - Check `storage/logs/laravel.log` for the 6-digit code
   - Code format: `verify code for email@example.com: 123456`

3. **ERROR HANDLING**
   ```dart
   if (response.statusCode == 400) {
     // Invalid OTP code - let user retry
   } else if (response.statusCode == 429) {
     // Too many attempts - show rate limit message
   }
   ```

4. **TOKEN STORAGE**
   - Access token: Use for API requests `Authorization: Bearer {token}`
   - Refresh token: Store securely for future use (currently not needed as tokens last 100 years)
   - User data: Store for displaying user info

5. **PROTECTED ROUTES**
   All these routes now require email verification:
   - `/api/bookings/*`
   - `/api/tickets/*`
   - `/api/payments/*`
   - `/api/v1/organizer/*`
   - etc.

## Quick Implementation Checklist

- [ ] After registration, navigate to OTP screen (don't try to use the app)
- [ ] Store user email locally for verification requests
- [ ] Create simple 6-digit OTP input screen
- [ ] Only store tokens after successful verification
- [ ] Add resend functionality with 60-second cooldown
- [ ] Handle rate limiting (429 status code)
- [ ] Clear OTP fields on error for easy retry

## Testing

1. Register a new user
2. Check Laravel logs for OTP code: `tail -f storage/logs/laravel.log`
3. Enter the 6-digit code
4. Receive tokens and access the app

That's it. Simple, secure, no complexity.