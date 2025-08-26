<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebAuthController extends Controller
{
    protected AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle web login
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ], [
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'password.required' => 'Please enter your password.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        // Attempt to authenticate - always remember the user
        if (Auth::attempt($credentials, true)) { // Always set remember to true
            $request->session()->regenerate();

            $user = Auth::user();

            // Check if email verification is needed (admins don't need verification)
            if (! $user->email_verified_at && $user->role !== 'admin') {
                return response()->json([
                    'status' => 'success',
                    'requires_verification' => true,
                    'message' => 'Please verify your email',
                    'user' => $user,
                    'redirect' => '/email/verify',
                ]);
            }

            // Log activity
            ActivityService::logUser('login', $user, 'User logged in via web');

            // Check if there's a redirect parameter (e.g., from checkout flow)
            $intendedRedirect = $request->input('redirect') ?? session('url.intended');

            // If there's an intended redirect (like from checkout), use it
            // Otherwise, redirect based on role
            if ($intendedRedirect && ! str_contains($intendedRedirect, 'login') && ! str_contains($intendedRedirect, 'register')) {
                $redirectPath = $intendedRedirect;
            } else {
                // Redirect to appropriate dashboard or home for regular users
                $redirectPath = match ($user->role) {
                    'admin' => '/admin',
                    'organizer' => '/organizer/dashboard',
                    'user' => '/', // Regular users go to home page since no user dashboard exists
                    default => '/'
                };
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'user' => $user,
                'redirect' => $redirectPath,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid email or password. Please check your credentials and try again.',
        ], 401);
    }

    /**
     * Get current user from session
     */
    public function getUser(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not authenticated',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    /**
     * Handle web registration
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:4',
                'phone_number' => 'nullable|string|unique:users,phone_number',
            ], [
                'email.unique' => 'This email address is already registered. Please use a different email or login instead.',
                'phone_number.unique' => 'This phone number is already registered. Please use a different phone number.',
                'password.min' => 'Password must be at least 4 characters long.',
                'full_name.required' => 'Please enter your full name.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'id' => Str::uuid(),
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone_number' => $validated['phone_number'] ?? null,
                'role' => 'user',
            ]);

            // Generate OTP for email verification
            $this->authService->generateOTP($user, 'verify');

            // Log user in to web session
            Auth::login($user);

            DB::commit();

            // Log activity
            ActivityService::logUser('registered', $user, 'New user registered');

            // Check if there's a redirect parameter (e.g., from checkout flow)
            $intendedRedirect = $request->input('redirect');

            // Store the intended redirect in session for after email verification
            if ($intendedRedirect && ! str_contains($intendedRedirect, 'login') && ! str_contains($intendedRedirect, 'register')) {
                session(['url.intended' => $intendedRedirect]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful. Please verify your email.',
                'user' => $user,
                'redirect' => '/email/verify',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Check for specific database errors and provide friendly messages
            $errorMessage = 'Registration failed. Please try again.';

            if (str_contains($e->getMessage(), 'users_email_unique')) {
                $errorMessage = 'This email address is already registered. Please use a different email or login instead.';
            } elseif (str_contains($e->getMessage(), 'users_phone_number_unique')) {
                $errorMessage = 'This phone number is already registered. Please use a different phone number.';
            } elseif (str_contains($e->getMessage(), 'connection')) {
                $errorMessage = 'Unable to connect to our servers. Please check your internet connection and try again.';
            }

            return response()->json([
                'status' => 'error',
                'message' => $errorMessage,
            ], 400);
        }
    }

    /**
     * Handle organizer registration
     */
    public function registerOrganizer(Request $request)
    {
        try {
            $validated = $request->validate([
                'business_name' => 'required|string|max:255',
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:4',
                'phone_number' => 'required|string|unique:users,phone_number',
            ], [
                'business_name.required' => 'Please enter your business or organization name.',
                'full_name.required' => 'Please enter your full name.',
                'email.required' => 'Please enter your business email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'This email address is already registered. Please use a different email or login instead.',
                'password.min' => 'Password must be at least 4 characters long.',
                'phone_number.required' => 'Please enter your phone number.',
                'phone_number.unique' => 'This phone number is already registered. Please use a different phone number.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'id' => Str::uuid(),
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone_number' => $validated['phone_number'],
                'role' => 'organizer',
            ]);

            // Create organizer profile
            $organizer = Organizer::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
                'business_country' => 'KE',
                'business_timezone' => 'Africa/Nairobi',
                'default_currency' => config('currencies.default', 'USD'),
                'commission_rate' => 10.00,
                'settlement_period_days' => 7,
                'is_active' => true,
                'is_verified' => false, // Requires admin verification
                'verification_status' => 'pending', // pending, approved, rejected
            ]);

            // Generate OTP for email verification
            $this->authService->generateOTP($user, 'verify');

            // Log user in to web session
            Auth::login($user);

            DB::commit();

            // Log activity
            ActivityService::logOrganizer('registered', $organizer, 'New organizer registered: '.$organizer->business_name);

            return response()->json([
                'status' => 'success',
                'message' => 'Organizer registration successful. Please verify your email.',
                'user' => $user,
                'organizer' => $organizer,
                'redirect' => '/email/verify',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Check for specific database errors and provide friendly messages
            $errorMessage = 'Registration failed. Please try again.';

            if (str_contains($e->getMessage(), 'users_email_unique')) {
                $errorMessage = 'This email address is already registered. Please use a different email or login instead.';
            } elseif (str_contains($e->getMessage(), 'users_phone_number_unique')) {
                $errorMessage = 'This phone number is already registered. Please use a different phone number.';
            } elseif (str_contains($e->getMessage(), 'organizers_business_name_unique')) {
                $errorMessage = 'This business name is already registered. Please use a different business name.';
            } elseif (str_contains($e->getMessage(), 'connection')) {
                $errorMessage = 'Unable to connect to our servers. Please check your internet connection and try again.';
            }

            // Log the actual error for debugging
            \Log::error('Organizer registration error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $errorMessage,
            ], 400);
        }
    }

    /**
     * Verify email with OTP
     */
    public function verifyEmail(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string|size:6',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Verification code must be 6 digits',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (! $user) {
            \Log::info('No authenticated user in session during verification');

            return response()->json([
                'status' => 'error',
                'message' => 'Not authenticated. Please login and try again.',
            ], 401);
        }

        // Validate OTP
        if (! $this->authService->validateOTP($user, $request->code, 'verify')) {
            // Log for debugging in development
            if (config('app.debug')) {
                \Log::info('Verification failed', [
                    'user_id' => $user->id,
                    'submitted_code' => $request->code,
                    'cached_code' => cache()->get("verify_code_{$user->id}"),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid verification code',
            ], 400);
        }

        // Mark email as verified
        $this->authService->verifyEmail($user);

        // Check if there's an intended URL from registration (like checkout flow)
        $intendedUrl = session('url.intended');

        if ($intendedUrl && ! str_contains($intendedUrl, 'login') && ! str_contains($intendedUrl, 'register') && ! str_contains($intendedUrl, 'verify')) {
            // Clear the intended URL from session
            session()->forget('url.intended');
            $redirectPath = $intendedUrl;
        } else {
            // Only redirect to role-based dashboards if they exist
            // For regular users, redirect to home since we don't have a user dashboard
            $redirectPath = match ($user->role) {
                'organizer' => '/organizer/dashboard',
                'admin' => '/admin',
                'user' => '/', // Redirect users to home page instead of non-existent dashboard
                default => '/'
            };
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully',
            'data' => [
                'redirect' => $redirectPath,
                'user' => $user,
            ],
        ]);
    }

    /**
     * Resend verification code
     */
    public function resendVerification(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not authenticated',
            ], 401);
        }

        // Generate new OTP
        $this->authService->generateOTP($user, 'verify');

        return response()->json([
            'status' => 'success',
            'message' => 'Verification code sent',
        ]);
    }

    /**
     * Check authentication status
     */
    public function check(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Load organizer relationship if user is organizer
            if ($user->role === 'organizer') {
                $user->load('organizer');
            }

            // Determine correct redirect based on role
            $redirectPath = match ($user->role) {
                'admin' => '/admin',
                'organizer' => '/organizer/dashboard',
                'user' => '/',
                default => '/'
            };

            return response()->json([
                'authenticated' => true,
                'user' => $user,
                'redirect' => $redirectPath,
                'email_verified' => $user->email_verified_at !== null || $user->role === 'admin',
            ]);
        }

        return response()->json([
            'authenticated' => false,
            'user' => null,
            'redirect' => null,
        ]);
    }

    /**
     * Logout - COMPLETELY clear session and force re-login
     */
    public function logout(Request $request)
    {
        // Log the logout activity before clearing session
        if (Auth::check()) {
            $user = Auth::user();
            ActivityService::logUser('logout', $user, 'User logged out');
        }

        // Clear the authentication
        Auth::logout();

        // Invalidate the session completely
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Clear any remember me cookies
        if ($request->hasCookie('remember_web')) {
            \Cookie::queue(\Cookie::forget('remember_web'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
            'redirect' => '/', // Always redirect to home after logout
        ]);
    }
}
