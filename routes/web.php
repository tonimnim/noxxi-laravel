<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Home route - Vue app
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Vue Example Route
Route::get('/vue-example', function () {
    return view('vue-example');
})->name('vue.example');

// Authentication Routes - Will be handled by Vue
Route::get('/login', function () {
    return view('auth.login-vue');
})->name('login');

Route::get('/register', function () {
    return view('auth.register-vue');
})->name('register');

Route::get('/register/organizer', function () {
    return view('auth.organizer-register-vue');
})->name('register.organizer');

// Web-based authentication routes (for session-based auth)
Route::post('/auth/web/login', [App\Http\Controllers\Api\AuthController::class, 'login'])->name('auth.web.login');

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
        'user' => '/user',
        default => '/'
    };

    return redirect($redirectPath)->with('success', 'Email verified successfully!');
})->name('auth.verified.login');
