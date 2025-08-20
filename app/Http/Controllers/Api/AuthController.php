<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\OrganizerRegisterRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Organizer;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\ActivityService;
use App\Services\AuthenticationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam full_name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Must be unique. Example: john@example.com
     * @bodyParam password string required The user's password. Min 8 characters in production. Example: SecurePass123!
     * @bodyParam phone_number string optional The user's phone number with country code. Example: +254712345678
     *
     * @response 201 {
     *   "status": "success",
     *   "message": "Registration successful. Please verify your email.",
     *   "data": {
     *     "user": {
     *       "id": "123e4567-e89b-12d3-a456-426614174000",
     *       "full_name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
     *     "expires_at": "2025-01-16T12:00:00Z"
     *   }
     * }
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'id' => Str::uuid(),
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'user',
        ]);

        // Generate OTP for email verification
        $this->authService->generateOTP($user, 'verify');

        // Generate tokens
        $tokens = $this->authService->generateTokens($user);

        // Log user registration activity
        ActivityService::logUser('registered', $user, 'New user registered: ' . $user->full_name);

        return $this->created([
            'user' => $user,
            'token' => $tokens['access_token'],
            'expires_at' => $tokens['expires_at'],
        ], 'Registration successful. Please verify your email.');
    }

    /**
     * Register a new organizer
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam full_name string required The organizer's full name. Example: Jane Smith
     * @bodyParam email string required The organizer's email address. Must be unique. Example: jane@events.com
     * @bodyParam password string required The organizer's password. Min 8 characters in production. Example: SecurePass123!
     * @bodyParam phone_number string required The organizer's phone number with country code. Example: +254712345678
     * @bodyParam business_name string required The business/organization name. Example: Amazing Events Ltd
     * @bodyParam business_country string optional Country code. Default: KE. Example: NG
     * @bodyParam business_timezone string optional Timezone. Default: Africa/Nairobi. Example: Africa/Lagos
     * @bodyParam default_currency string optional Default currency. Default: KES. Example: NGN
     *
     * @response 201 {
     *   "status": "success",
     *   "message": "Organizer registration successful. Please verify your email.",
     *   "data": {
     *     "user": {
     *       "id": "123e4567-e89b-12d3-a456-426614174000",
     *       "full_name": "Jane Smith",
     *       "email": "jane@events.com",
     *       "role": "organizer"
     *     },
     *     "organizer": {
     *       "id": "456e7890-e89b-12d3-a456-426614174000",
     *       "business_name": "Amazing Events Ltd",
     *       "business_country": "NG",
     *       "default_currency": "NGN"
     *     },
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
     *     "expires_at": "2025-01-16T12:00:00Z"
     *   }
     * }
     */
    public function registerOrganizer(OrganizerRegisterRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Create user account
            $user = User::create([
                'id' => Str::uuid(),
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($validated['password']),
                'role' => 'organizer',
            ]);

            // Create organizer profile
            $organizer = Organizer::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
                'business_country' => $validated['business_country'] ?? 'KE',
                'business_timezone' => $validated['business_timezone'] ?? 'Africa/Nairobi',
                'default_currency' => $validated['default_currency'] ?? 'KES',
                'commission_rate' => $validated['commission_rate'] ?? 10.00,
                'settlement_period_days' => $validated['settlement_period_days'] ?? 7,
                'is_active' => true,
            ]);

            DB::commit();

            // Generate OTP for email verification
            $this->authService->generateOTP($user, 'verify');

            // Generate tokens
            $tokens = $this->authService->generateTokens($user, 'organizer-app');

            // Log organizer registration activity
            ActivityService::logOrganizer('registered', $organizer, 'New organizer registered: ' . $organizer->business_name);

            return $this->created([
                'user' => $user,
                'organizer' => $organizer,
                'token' => $tokens['access_token'],
                'expires_at' => $tokens['expires_at'],
            ], 'Organizer registration successful. Please verify your email.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Organizer registration failed: '.$e->getMessage());

            return $this->error('Registration failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Login user
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: SecurePass123!
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Login successful",
     *   "data": {
     *     "user": {
     *       "id": "123e4567-e89b-12d3-a456-426614174000",
     *       "full_name": "John Doe",
     *       "email": "john@example.com",
     *       "email_verified_at": "2025-01-15T10:00:00Z",
     *       "role": "user"
     *     },
     *     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
     *     "token_type": "Bearer",
     *     "expires_at": "2025-01-16T12:00:00Z",
     *     "refresh_token": "def50200...",
     *     "refresh_expires_at": "2025-02-14T12:00:00Z"
     *   }
     * }
     * @response 401 {
     *   "status": "error",
     *   "message": "Invalid credentials"
     * }
     * @response 403 {
     *   "status": "error",
     *   "message": "Account is locked. Please try again in 30 minutes."
     * }
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        // Authenticate user
        $authResult = $this->authService->authenticate($credentials, $request);

        if (! $authResult['success']) {
            if ($authResult['code'] === 403) {
                return $this->forbidden($authResult['message']);
            }

            return $this->unauthorized($authResult['message']);
        }

        $user = $authResult['user'];

        // Load organizer relationship if needed
        if ($user->role === 'organizer') {
            $user->load('organizer');
        }

        // Generate tokens
        $tokens = $this->authService->generateTokens($user);

        // Log login activity
        ActivityService::logUser('login', $user, 'User logged in: ' . $user->full_name);

        return $this->success([
            'user' => $user,
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_at' => $tokens['expires_at'],
        ], 'Login successful');
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'organizer') {
            $user->load('organizer');
        }

        return $this->success(['user' => $user]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20|unique:users,phone_number,' . $user->id,
            'city' => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
            'notification_preferences' => 'sometimes|array',
            'notification_preferences.email' => 'boolean',
            'notification_preferences.sms' => 'boolean',
            'notification_preferences.push' => 'boolean',
        ]);

        if (isset($validated['notification_preferences'])) {
            $validated['notification_preferences'] = array_merge(
                $user->notification_preferences ?? [],
                $validated['notification_preferences']
            );
        }

        $user->update($validated);

        return $this->success(['user' => $user->fresh()], 'Profile updated successfully');
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->error('Current password is incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($validated['new_password'])
        ]);

        return $this->success(null, 'Password changed successfully');
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Verify email with OTP
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = $request->user();

        // Validate OTP
        if (! $this->authService->validateOTP($user, $request->code, 'verify')) {
            return $this->error('Invalid verification code', 400);
        }

        // Mark email as verified
        $this->authService->verifyEmail($user);

        // Generate login token for web redirect
        $loginToken = Str::random(60);
        cache()->put('email_verified_login_'.$loginToken, $user->id, 300);

        return $this->success([
            'redirect' => '/auth/verified-login?token='.$loginToken,
        ], 'Email verified successfully');
    }

    /**
     * Resend verification code
     */
    public function resendVerification(Request $request)
    {
        $user = $request->user();

        // Generate new OTP
        $this->authService->generateOTP($user, 'verify');

        return $this->success(null, 'Verification code sent');
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        // Generate reset OTP
        $this->authService->generateOTP($user, 'reset');

        return $this->success(null, 'Password reset code sent');
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request)
    {
        $passwordRule = app()->environment('production')
            ? StrongPassword::production()
            : StrongPassword::development();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => ['required', 'string', 'confirmed', $passwordRule],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        // Validate OTP
        if (! $this->authService->validateOTP($user, $request->code, 'reset')) {
            return $this->error('Invalid reset code', 400);
        }

        // Reset password
        $this->authService->resetPassword($user, $request->password);

        return $this->success(null, 'Password reset successfully');
    }

    /**
     * Refresh access token
     */
    public function refreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Rotate tokens
        $tokens = $this->authService->rotateTokens($request->refresh_token);

        if (! $tokens) {
            return $this->unauthorized('Invalid refresh token');
        }

        return $this->success([
            'token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_at' => $tokens['expires_at'],
        ], 'Token refreshed successfully');
    }

    /**
     * Get redirect path based on user role
     */
    protected function getRedirectPath($user): string
    {
        return match ($user->role) {
            'admin' => '/admin',
            'organizer' => '/organizer/dashboard',
            'user' => '/my-account',
            default => '/'
        };
    }
}
