<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Home route - Vue app
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Listing (Event) Routes - Vue app handles these
Route::get('/listings/{identifier}', [App\Http\Controllers\Web\ListingController::class, 'show'])->name('listings.show');

// Category listing pages - Vue app handles these
Route::get('/events', function () {
    return view('welcome');
})->name('events.index');
Route::get('/sports', function () {
    return view('welcome');
})->name('sports.index');
Route::get('/cinema', function () {
    return view('welcome');
})->name('cinema.index');
Route::get('/experiences', function () {
    return view('welcome');
})->name('experiences.index');
Route::get('/checkout/{eventId}', function ($eventId) {
    return view('checkout', ['eventId' => $eventId]);
})->name('checkout');
Route::get('/booking/confirmation/{bookingId}', function ($bookingId) {
    return view('booking-confirmation', ['bookingId' => $bookingId]);
})->name('booking.confirmation')->middleware('auth');

// User Account Page
Route::get('/account', [App\Http\Controllers\Web\AccountController::class, 'index'])
    ->name('account')
    ->middleware('auth');

// User Tickets API (for session-based auth)
Route::middleware('auth')->prefix('user')->group(function () {
    Route::get('/tickets/upcoming', [\App\Http\Controllers\Web\UserTicketController::class, 'upcoming']);
    Route::get('/tickets/past', [\App\Http\Controllers\Web\UserTicketController::class, 'past']);
    Route::get('/tickets/{id}', [\App\Http\Controllers\Web\UserTicketController::class, 'show']);

    // Secure QR code generation (on-demand, never stored)
    Route::get('/tickets/{id}/qr', [\App\Http\Controllers\Web\SecureQrController::class, 'generateQr'])
        ->name('user.ticket.qr')
        ->middleware('throttle:20,1'); // 20 requests per minute
});

// Ticket Scanner Routes (Web-based scanning)
Route::middleware(['auth', 'can.scan'])->prefix('scanner')->group(function () {
    // Scanner UI page
    Route::get('/check-in', [\App\Http\Controllers\Web\ScannerController::class, 'index'])
        ->name('scanner.index');

    // Scanner API endpoints
    Route::post('/validate', [\App\Http\Controllers\Web\ScannerController::class, 'validateTicket'])
        ->name('scanner.validate')
        ->middleware('throttle:60,1'); // 60 validations per minute

    Route::post('/check-in', [\App\Http\Controllers\Web\ScannerController::class, 'checkIn'])
        ->name('scanner.checkin')
        ->middleware('throttle:30,1'); // 30 check-ins per minute

    // Get event manifest for offline scanning
    Route::get('/event/{event_id}/manifest', [\App\Http\Controllers\Web\ScannerController::class, 'getManifest'])
        ->name('scanner.manifest')
        ->middleware('throttle:10,1'); // 10 manifest downloads per minute

    // Get check-in statistics
    Route::get('/event/{event_id}/stats', [\App\Http\Controllers\Web\ScannerController::class, 'getStats'])
        ->name('scanner.stats')
        ->middleware('throttle:120,1'); // 120 stats requests per minute
});

// Refund Requests (for session-based auth)
Route::middleware('auth')->prefix('refund-requests')->group(function () {
    Route::post('/', [\App\Http\Controllers\Web\RefundRequestController::class, 'store'])->name('refund-requests.store');
});

// Vue Example Route
Route::get('/vue-example', function () {
    return view('vue-example');
})->name('vue.example');

// Authentication Routes - Will be handled by Vue

// User Authentication
Route::get('/login', function () {
    return view('auth.login-vue');
})->name('login');

Route::get('/register', function () {
    return view('auth.register-vue');
})->name('register');

// Organizer Authentication
Route::get('/login/organizer', function () {
    return view('auth.organizer-login-vue');
})->name('login.organizer');

Route::get('/register/organizer', function () {
    return view('auth.organizer-register-vue');
})->name('register.organizer');

// Web Booking and Payment Routes (session-based)
Route::middleware('auth:web')->group(function () {
    Route::post('/web/bookings', [App\Http\Controllers\Web\BookingController::class, 'store'])->name('web.bookings.store');
    Route::get('/web/bookings/{id}', [App\Http\Controllers\Web\BookingController::class, 'show'])->name('web.bookings.show');
    Route::post('/web/payments/paystack/initialize', [App\Http\Controllers\Web\PaymentController::class, 'initializePaystack'])->name('web.payments.paystack');
});

// Payment callback (doesn't require auth as user comes from Paystack)
Route::get('/payment/callback', [App\Http\Controllers\Web\PaymentController::class, 'handleCallback'])->name('payment.callback');

// Web Authentication Routes (session-based)
Route::prefix('auth/web')->group(function () {
    Route::post('/login', [App\Http\Controllers\Auth\WebAuthController::class, 'login'])->name('auth.web.login');
    Route::post('/register', [App\Http\Controllers\Auth\WebAuthController::class, 'register'])->name('auth.web.register');
    Route::post('/register-organizer', [App\Http\Controllers\Auth\WebAuthController::class, 'registerOrganizer'])->name('auth.web.register.organizer');
    Route::get('/check', [App\Http\Controllers\Auth\WebAuthController::class, 'check'])->name('auth.web.check');

    // Protected routes (require authentication)
    Route::middleware('auth:web')->group(function () {
        Route::get('/user', [App\Http\Controllers\Auth\WebAuthController::class, 'getUser'])->name('auth.web.user');
        Route::post('/verify-email', [App\Http\Controllers\Auth\WebAuthController::class, 'verifyEmail'])->name('auth.web.verify');
        Route::post('/resend-verification', [App\Http\Controllers\Auth\WebAuthController::class, 'resendVerification'])->name('auth.web.resend');
        Route::post('/logout', [App\Http\Controllers\Auth\WebAuthController::class, 'logout'])->name('auth.web.logout');
    });
});

// Logout route
Route::post('/logout', function (Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Email Verification Routes (Vue)
Route::get('/email/verify', function () {
    return view('auth.verify-email-vue');
})->name('verification.notice');

// Keep the old email link verification for backward compatibility
Route::middleware('auth')->group(function () {
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        // Redirect based on user role
        $user = $request->user();
        $redirectPath = match ($user->role) {
            'admin' => '/admin',
            'organizer' => '/organizer/dashboard',
            'user' => '/my-account',
            default => '/'
        };

        return redirect($redirectPath)->with('verified', 'Email successfully verified!');
    })->middleware('signed')->name('verification.verify');
});

// Password Reset Routes (Vue)
Route::get('/password/reset', function () {
    return view('auth.password-reset-vue');
})->name('password.reset');

// Verified Login Route (handles email verification redirect)
Route::get('/auth/verified-login', function (Request $request) {
    $token = $request->get('token');
    if (! $token) {
        return redirect('/login')->with('error', 'Invalid verification link');
    }

    $userId = cache()->get('email_verified_login_'.$token);
    if (! $userId) {
        return redirect('/login')->with('error', 'Verification link expired');
    }

    $user = \App\Models\User::find($userId);
    if (! $user) {
        return redirect('/login')->with('error', 'User not found');
    }

    // Log user into web session
    auth()->login($user, true);
    cache()->forget('email_verified_login_'.$token);

    // Redirect based on user role
    $redirectPath = match ($user->role) {
        'admin' => '/admin',
        'organizer' => '/organizer/dashboard',
        'user' => '/',
        default => '/'
    };

    return redirect($redirectPath)->with('success', 'Email verified successfully!');
})->name('auth.verified.login');

// Legal Pages (with caching)
Route::middleware('cache.static')->group(function () {
    Route::get('/terms-of-service', function () {
        return view('legal.terms');
    })->name('terms.service');

    Route::get('/privacy-policy', function () {
        return view('legal.privacy');
    })->name('privacy.policy');

    Route::get('/refund-policy', function () {
        return view('legal.refund');
    })->name('refund.policy');

    Route::get('/cookie-policy', function () {
        return view('legal.cookies');
    })->name('cookie.policy');
});

// Organizer Payout Receipt Routes
Route::middleware('auth:web')->prefix('organizer')->group(function () {
    Route::get('/payout/{id}/receipt', [App\Http\Controllers\Organizer\PayoutReceiptController::class, 'view'])
        ->name('organizer.payout.receipt');
    Route::get('/payout/{id}/receipt/download', [App\Http\Controllers\Organizer\PayoutReceiptController::class, 'download'])
        ->name('organizer.payout.receipt.download');
});
