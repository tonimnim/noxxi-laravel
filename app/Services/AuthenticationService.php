<?php

namespace App\Services;

use App\Events\UserFailedLogin;
use App\Events\UserLockedOut;
use App\Events\UserLoggedIn;
use App\Events\UserPasswordReset;
use App\Jobs\SendPasswordResetCode;
use App\Jobs\SendVerificationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthenticationService
{
    /**
     * Maximum failed login attempts before lockout
     */
    protected int $maxAttempts = 5;

    /**
     * Lockout duration in minutes
     */
    protected int $lockoutMinutes = 30;

    /**
     * Authenticate user with credentials
     */
    public function authenticate(array $credentials, Request $request): array
    {
        $email = strtolower(trim($credentials['email']));
        $password = $credentials['password'];

        // Find user by email
        $user = User::where('email', $email)->first();

        // Check if account exists
        if (! $user) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'code' => 401,
            ];
        }

        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            $minutes = $this->minutesUntilUnlock($user);
            event(new UserLockedOut($user, $request));

            return [
                'success' => false,
                'message' => "Account is locked. Please try again in {$minutes} minutes.",
                'code' => 403,
            ];
        }

        // Verify password
        if (! Hash::check($password, $user->password)) {
            $this->handleFailedLogin($user, $request);

            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'code' => 401,
            ];
        }

        // Check if account is active
        if (! $user->is_active) {
            return [
                'success' => false,
                'message' => 'Account deactivated',
                'code' => 403,
            ];
        }

        // Successful authentication
        $this->handleSuccessfulLogin($user, $request);

        return [
            'success' => true,
            'user' => $user,
            'message' => 'Authentication successful',
        ];
    }

    /**
     * Generate access and refresh tokens for user
     */
    public function generateTokens(User $user, ?string $tokenName = null): array
    {
        // Determine token name based on user role
        if (! $tokenName) {
            $tokenName = match ($user->role) {
                'organizer' => 'organizer-app',
                'admin' => 'admin-panel',
                default => 'mobile-app',
            };
        }

        // Revoke existing tokens for this device/app
        $user->tokens()->where('name', $tokenName)->delete();

        // Create new access token
        $tokenResult = $user->createToken($tokenName);
        $accessToken = $tokenResult->accessToken;

        // Calculate expiry times - set to 100 years (effectively never expires)
        $accessTokenExpiry = now()->addYears(100);
        $refreshTokenExpiry = now()->addYears(100);

        // Store token metadata in cache for rotation tracking
        // Use hash of token as key to avoid length issues
        $tokenMeta = [
            'user_id' => $user->id,
            'created_at' => now()->toIso8601String(),
            'last_used' => now()->toIso8601String(),
            'rotation_count' => 0,
        ];
        $tokenKey = 'token_meta_'.hash('sha256', $accessToken);
        cache()->put($tokenKey, $tokenMeta, $refreshTokenExpiry);

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $accessTokenExpiry->toIso8601String(),
            'refresh_token' => $this->generateRefreshToken($user),
            'refresh_expires_at' => $refreshTokenExpiry->toIso8601String(),
        ];
    }

    /**
     * Generate a refresh token
     */
    protected function generateRefreshToken(User $user): string
    {
        $refreshToken = Str::random(80);

        // Store refresh token in cache with user ID
        cache()->put(
            "refresh_token_{$refreshToken}",
            [
                'user_id' => $user->id,
                'created_at' => now()->toIso8601String(),
            ],
            now()->addYears(100)
        );

        return $refreshToken;
    }

    /**
     * Rotate tokens using refresh token
     */
    public function rotateTokens(string $refreshToken): ?array
    {
        $tokenData = cache()->get("refresh_token_{$refreshToken}");

        if (! $tokenData) {
            return null;
        }

        $user = User::find($tokenData['user_id']);

        if (! $user || ! $user->is_active) {
            cache()->forget("refresh_token_{$refreshToken}");

            return null;
        }

        // Invalidate old refresh token
        cache()->forget("refresh_token_{$refreshToken}");

        // Generate new tokens
        return $this->generateTokens($user);
    }

    /**
     * Validate OTP code
     */
    public function validateOTP(User $user, string $code, string $type = 'verify'): bool
    {
        $cacheKey = match ($type) {
            'verify' => "verify_code_{$user->id}",
            'reset' => "reset_code_{$user->email}",
            default => null,
        };

        if (! $cacheKey) {
            return false;
        }

        $storedCode = cache()->get($cacheKey);

        if (! $storedCode || $storedCode !== $code) {
            return false;
        }

        // Clear the code after successful validation
        cache()->forget($cacheKey);

        return true;
    }

    /**
     * Generate and send OTP code
     */
    public function generateOTP(User $user, string $type = 'verify'): string
    {
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $cacheKey = match ($type) {
            'verify' => "verify_code_{$user->id}",
            'reset' => "reset_code_{$user->email}",
            default => null,
        };

        $duration = match ($type) {
            'verify' => 600, // 10 minutes
            'reset' => 900, // 15 minutes for password reset
            default => 600,
        };

        if ($cacheKey) {
            cache()->put($cacheKey, $code, $duration);
        }

        // Dispatch job to send the code
        if ($type === 'verify') {
            // In development, run synchronously to ensure immediate processing
            if (app()->environment('local', 'development')) {
                SendVerificationCode::dispatchSync($user, $code);
            } else {
                SendVerificationCode::dispatch($user, $code);
            }
        } elseif ($type === 'reset') {
            // In development, run synchronously to ensure immediate processing
            if (app()->environment('local', 'development')) {
                SendPasswordResetCode::dispatchSync($user, $code);
            } else {
                SendPasswordResetCode::dispatch($user, $code);
            }
        }

        // Log in development only for debugging
        if (config('app.debug') && app()->environment('local', 'development')) {
            \Log::info("{$type} code for {$user->email}: {$code}");
        }

        return $code;
    }

    /**
     * Lock user account
     */
    public function lockAccount(User $user, ?int $minutes = null): void
    {
        $minutes = $minutes ?? $this->lockoutMinutes;

        $user->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);

        event(new UserLockedOut($user, request()));
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(User $user): void
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Check if account is locked
     */
    public function isAccountLocked(User $user): bool
    {
        return $user->locked_until && $user->locked_until->isFuture();
    }

    /**
     * Get minutes until account unlock
     */
    public function minutesUntilUnlock(User $user): int
    {
        if (! $this->isAccountLocked($user)) {
            return 0;
        }

        return now()->diffInMinutes($user->locked_until, false) + 1;
    }

    /**
     * Handle failed login attempt
     */
    protected function handleFailedLogin(User $user, Request $request): void
    {
        $user->failed_login_attempts++;

        // Log the failed attempt
        $this->logLoginAttempt($user, $request, false);

        // Lock account if max attempts reached
        if ($user->failed_login_attempts >= $this->maxAttempts) {
            $this->lockAccount($user);
        } else {
            $user->save();
        }

        event(new UserFailedLogin($user, $request));
    }

    /**
     * Handle successful login
     */
    protected function handleSuccessfulLogin(User $user, Request $request): void
    {
        // Reset failed attempts and update login info
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_ip' => $request->ip(),
            'last_login_at' => now(),
        ]);

        // Log the successful attempt
        $this->logLoginAttempt($user, $request, true);

        // Update last active timestamp
        $user->updateLastActive();

        event(new UserLoggedIn($user, $request));
    }

    /**
     * Log login attempt in user's history
     */
    protected function logLoginAttempt(User $user, Request $request, bool $success): void
    {
        $loginHistory = $user->login_history ?? [];

        $loginHistory[] = [
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
            'status' => $success ? 'success' : 'failed',
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
        ];

        // Keep only last 20 attempts
        $loginHistory = array_slice($loginHistory, -20);

        $user->login_history = $loginHistory;
        $user->save();
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword) {
            // Update password
            $user->update([
                'password' => Hash::make($newPassword),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            // Revoke all existing tokens
            $user->tokens()->delete();

            // Clear any refresh tokens
            // This would require tracking refresh tokens per user in production

            event(new UserPasswordReset($user));
        });
    }

    /**
     * Verify user email
     */
    public function verifyEmail(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}
