<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\EventCategoryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventSearchController;
use App\Http\Controllers\Api\EventTrendingController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\RefundRequestController;
use App\Http\Controllers\Api\SystemHealthController;
use App\Http\Controllers\Api\V1\OrganizerListingController;
use App\Http\Controllers\Api\V1\TicketValidationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Home page routes
Route::get('/home/trending', [HomeController::class, 'trending']);
Route::get('/home/featured', [HomeController::class, 'featured']);

// Public auth routes with rate limiting
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('register-organizer', [AuthController::class, 'registerOrganizer'])->middleware('throttle:auth');
    Route::post('organizer/register', [AuthController::class, 'registerOrganizer'])->middleware('throttle:auth'); // Alternative route
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('check', [AuthController::class, 'checkAuth']); // Check if user is authenticated
    
    // Email verification routes (public)
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:verification');
    Route::post('resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:verification');
    Route::get('verification-status', [AuthController::class, 'verificationStatus'])->middleware('throttle:verification');

    // Password reset routes (public) with stricter rate limiting
    Route::post('password/request-reset', [AuthController::class, 'requestPasswordReset'])->middleware('throttle:password-reset');
    Route::post('password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');

    // Token refresh route (public) with rate limiting
    Route::post('refresh', [AuthController::class, 'refreshToken'])->middleware('throttle:auth');
});

// Public event routes
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/upcoming', [EventController::class, 'upcoming']);
    Route::get('/featured', [EventController::class, 'featured']);
    Route::get('/trending', [EventTrendingController::class, 'trending']);
    Route::get('/categories', [EventCategoryController::class, 'index']);
    Route::get('/categories/{slug}', [EventCategoryController::class, 'show']);
    Route::get('/search', [EventSearchController::class, 'search']);
    Route::get('/search-suggestions', [EventSearchController::class, 'suggestions']);
    Route::get('/{id}', [EventController::class, 'show']);
    Route::get('/{id}/similar', [EventTrendingController::class, 'similar']);
});

// Category-specific routes
Route::get('/events-category', [EventController::class, 'events']);
Route::get('/experiences', [EventController::class, 'experiences']);
Route::get('/sports', [EventController::class, 'sports']);
Route::get('/cinema', [EventController::class, 'cinema']);

// Search route
Route::get('/search', [EventSearchController::class, 'search']);

// Location detection (IP-based)
Route::get('/location/detect', [LocationController::class, 'detect']);

// Cities routes (public)
Route::prefix('cities')->group(function () {
    Route::get('/', [CityController::class, 'index']);
    Route::get('/popular', [CityController::class, 'popular']);
    Route::get('/search', [CityController::class, 'search']);
    Route::get('/region/{region}', [CityController::class, 'byRegion']);
});

// System health endpoints (public but rate-limited)
Route::prefix('system')->middleware('throttle:60,1')->group(function () {
    Route::get('/health', [SystemHealthController::class, 'health']);
    Route::get('/metrics', [SystemHealthController::class, 'metrics']);
});

// Protected routes (require authentication and email verification)
Route::middleware(['auth:api', 'verified'])->group(function () {
    // Auth routes with rate limiting
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::get('user', [AuthController::class, 'me']); // Alias for Vue components
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // User profile routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
    
    // User settings routes (separate from profile)
    Route::prefix('settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SettingsController::class, 'index']);
        Route::put('/', [\App\Http\Controllers\Api\SettingsController::class, 'update']);
        Route::post('/reset', [\App\Http\Controllers\Api\SettingsController::class, 'reset']);
    });

    // Booking routes
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::get('/{id}', [BookingController::class, 'show']);
        Route::post('/{id}/cancel', [BookingController::class, 'cancel']);
        Route::get('/{id}/tickets', [BookingController::class, 'tickets']);
    });

    // Ticket routes
    Route::prefix('tickets')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TicketController::class, 'index']);
        Route::get('/upcoming', [\App\Http\Controllers\Api\TicketController::class, 'upcoming']);
        Route::get('/past', [\App\Http\Controllers\Api\TicketController::class, 'past']);
        Route::get('/booking/{bookingId}', [\App\Http\Controllers\Api\TicketController::class, 'byBooking']);
        Route::get('/{id}', [\App\Http\Controllers\Api\TicketController::class, 'show']);
        Route::post('/{id}/transfer', [\App\Http\Controllers\Api\TicketController::class, 'transfer']);
        Route::get('/{id}/transfer-history', [\App\Http\Controllers\Api\TicketController::class, 'transferHistory']);
        Route::get('/{id}/download', [\App\Http\Controllers\Api\TicketController::class, 'download']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::get('/preferences', [NotificationController::class, 'preferences']);
        Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
    });

    // Payment routes - Single endpoint for all payment methods (Paystack handles both card and M-Pesa)
    Route::prefix('payments')->group(function () {
        Route::post('/initialize', [PaymentController::class, 'initializePaystack']); // Handles card, M-Pesa, bank transfer
        Route::get('/verify/{transactionId}', [PaymentController::class, 'verifyPayment']);
        Route::get('/transactions', [PaymentController::class, 'transactions']);
    });
});

// Payment callback route (requires auth and verification but outside the main auth group for flexibility)
Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/payments/callback', [PaymentController::class, 'paymentCallback']);

    // Refund routes
    Route::prefix('refunds')->group(function () {
        Route::get('/', [RefundController::class, 'index']);
        Route::post('/', [RefundController::class, 'store']);
        Route::get('/{id}', [RefundController::class, 'show']);
        Route::post('/{id}/cancel', [RefundController::class, 'cancel']);
        Route::get('/check-eligibility/{bookingId}', [RefundController::class, 'checkEligibility']);
    });
    
    // Refund Request routes (new API for handling refund requests)
    Route::prefix('refund-requests')->group(function () {
        Route::get('/', [RefundRequestController::class, 'index']);
        Route::post('/', [RefundRequestController::class, 'store']);
        Route::get('/{id}', [RefundRequestController::class, 'show']);
        Route::put('/{id}/cancel', [RefundRequestController::class, 'cancel']);
    });
});

// API v1 Routes
Route::prefix('v1')->group(function () {
    // Organizer-only routes
    Route::middleware(['auth:api', 'verified', 'organizer'])->prefix('organizer')->group(function () {
        // Listing management
        Route::prefix('listings')->group(function () {
            Route::get('/', [OrganizerListingController::class, 'index']);
            Route::post('/create', [OrganizerListingController::class, 'store']);
            Route::get('/{id}', [OrganizerListingController::class, 'show']);
            Route::put('/{id}/update', [OrganizerListingController::class, 'update']);
            Route::post('/{id}/publish', [OrganizerListingController::class, 'publish']);
            Route::delete('/{id}', [OrganizerListingController::class, 'destroy']);
        });

        // Financial management
        Route::prefix('financial')->group(function () {
            Route::get('/summary', [\App\Http\Controllers\Api\OrganizerFinancialController::class, 'summary']);
            Route::get('/transactions', [\App\Http\Controllers\Api\OrganizerFinancialController::class, 'transactions']);
            Route::get('/commission-breakdown', [\App\Http\Controllers\Api\OrganizerFinancialController::class, 'commissionBreakdown']);
        });
    });

    // Ticket validation endpoints (for organizers/scanners)
    Route::middleware(['auth:api', 'verified'])->prefix('tickets')->group(function () {
        Route::post('/validate', [TicketValidationController::class, 'validate']);
        Route::post('/check-in', [TicketValidationController::class, 'checkIn']);
        Route::post('/batch-validate', [TicketValidationController::class, 'batchValidate']);
        Route::post('/validate-by-code', [TicketValidationController::class, 'validateByCode']);
        Route::post('/batch-check-in', [TicketValidationController::class, 'batchCheckIn']);
        Route::get('/batch-status/{batchId}', [TicketValidationController::class, 'getBatchStatus'])->name('api.v1.batch-status');
        
        // Secure QR code endpoints (on-demand generation)
        Route::get('/{id}/qr', [\App\Http\Controllers\Api\SecureQrController::class, 'generateQr'])
            ->middleware('throttle:10,1');
        Route::post('/qr/validate', [\App\Http\Controllers\Api\SecureQrController::class, 'validateQr'])
            ->middleware('throttle:30,1');
        Route::post('/qr/batch', [\App\Http\Controllers\Api\SecureQrController::class, 'batchGenerateQr'])
            ->middleware('throttle:5,1');
        
        // Cancelled tickets endpoints (for organizers)
        Route::middleware('organizer')->group(function () {
            Route::get('/cancelled', [\App\Http\Controllers\Api\V1\TicketController::class, 'cancelled']);
            Route::get('/cancelled/stats', [\App\Http\Controllers\Api\V1\TicketController::class, 'cancellationStats']);
            Route::post('/bulk-cancel', [\App\Http\Controllers\Api\V1\TicketController::class, 'bulkCancel']);
        });
    });

    // Event manifest for offline mode (accessible by organizers and authorized managers)
    Route::middleware(['auth:api', 'verified'])->prefix('events')->group(function () {
        Route::get('/{id}/manifest', [TicketValidationController::class, 'getManifest']);
        Route::get('/{id}/check-in-stats', [TicketValidationController::class, 'getCheckInStats']);
    });

    // Manager/Scanner management endpoints
    Route::middleware(['auth:api', 'verified', 'organizer'])->prefix('managers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\OrganizerManagerController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V1\OrganizerManagerController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\V1\OrganizerManagerController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\OrganizerManagerController::class, 'destroy']);
        Route::get('/activity', [\App\Http\Controllers\Api\V1\OrganizerManagerController::class, 'scanActivity']);
    });
});

// Legacy organizer routes (keeping for backward compatibility)
Route::middleware(['auth:api', 'verified', 'organizer'])->prefix('organizer')->group(function () {
    // Organizer event management (to be implemented)
    Route::prefix('events')->group(function () {
        // Route::get('/', [OrganizerEventController::class, 'index']);
        // Route::post('/', [OrganizerEventController::class, 'store']);
        // Route::put('/{id}', [OrganizerEventController::class, 'update']);
        // Route::delete('/{id}', [OrganizerEventController::class, 'destroy']);
        // Route::get('/{id}/bookings', [OrganizerEventController::class, 'bookings']);
        // Route::get('/{id}/analytics', [OrganizerEventController::class, 'analytics']);
    });

    // Ticket scanning (to be implemented)
    Route::prefix('scan')->group(function () {
        // Route::post('/validate', [ScanController::class, 'validateTicket']);
        // Route::post('/checkin', [ScanController::class, 'checkinTicket']);
    });

    // Organizer analytics (to be implemented)
    Route::prefix('analytics')->group(function () {
        // Route::get('/dashboard', [OrganizerAnalyticsController::class, 'dashboard']);
        // Route::get('/revenue', [OrganizerAnalyticsController::class, 'revenue']);
        // Route::get('/tickets', [OrganizerAnalyticsController::class, 'tickets']);
    });
});

// Admin-only routes
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    // Admin management routes (to be implemented)
    // Route::resource('users', AdminUserController::class);
    // Route::resource('organizers', AdminOrganizerController::class);
    // Route::resource('events', AdminEventController::class);
});

// Payment webhook callbacks (public, no auth required)
Route::prefix('webhooks')->group(function () {
    Route::post('/paystack', [PaymentController::class, 'paystackWebhook']);
    Route::post('/mpesa', [PaymentController::class, 'mpesaCallback']);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});
