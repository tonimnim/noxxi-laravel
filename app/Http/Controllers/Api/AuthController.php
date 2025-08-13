<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = User::create([
            'id' => Str::uuid(),
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
        ]);

        $token = $user->createToken('mobile-app')->accessToken;
        
        // Send verification code
        $this->sendVerificationCode($user);

        return $this->created([
            'user' => $user,
            'token' => $token,
        ], 'Registration successful');
    }
    
    public function registerOrganizer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Personal Information
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            
            // Business Information
            'business_name' => 'required|string|max:255',
            'business_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        DB::beginTransaction();
        
        try {
            // Create user account with organizer role
            $user = User::create([
                'id' => Str::uuid(),
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
                'role' => 'organizer',
            ]);

            // Create organizer profile
            $organizer = \App\Models\Organizer::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'business_email' => $request->business_email,
                'business_country' => 'KE', // Default to Kenya
                'business_timezone' => 'Africa/Nairobi',
                'default_currency' => 'KES',
                'commission_rate' => 10.00, // 10% default commission
                'settlement_period_days' => 7,
                'is_active' => true,
            ]);

            DB::commit();

            $token = $user->createToken('organizer-app')->accessToken;
            
            // Send verification code
            $this->sendVerificationCode($user);

            return $this->created([
                'user' => $user,
                'organizer' => $organizer,
                'token' => $token,
            ], 'Organizer registration successful');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Registration failed. Please try again.', 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Attempt login with web guard for session-based auth
        if (!Auth::guard('web')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return $this->unauthorized('Invalid credentials');
        }

        $user = Auth::guard('web')->user();

        if (!$user->is_active) {
            Auth::guard('web')->logout();
            return $this->forbidden('Account deactivated');
        }

        // Load organizer relationship if user is an organizer
        if ($user->role === 'organizer') {
            $user->load('organizer');
        }

        $user->updateLastActive();
        
        // Regenerate session for security
        $request->session()->regenerate();
        
        // Also create API token for API calls
        $tokenName = $user->role === 'organizer' ? 'organizer-app' : 'mobile-app';
        $token = $user->createToken($tokenName)->accessToken;

        return $this->success([
            'user' => $user,
            'token' => $token,
            'session_created' => true,
        ], 'Login successful');
    }

    public function me(Request $request)
    {
        return $this->success(['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return $this->success(null, 'Logged out');
    }

    // Email Verification - Simplified
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = $request->user();
        
        // Check code in cache (expires in 10 minutes)
        $storedCode = cache()->get('verify_code_' . $user->id);
        
        if (!$storedCode || $storedCode !== $request->code) {
            return $this->error('Invalid verification code', 400);
        }

        $user->markEmailAsVerified();
        cache()->forget('verify_code_' . $user->id);

        return $this->success(['redirect' => $this->getRedirectPath($user)], 'Email verified');
    }

    public function resendVerification(Request $request)
    {
        $this->sendVerificationCode($request->user());
        return $this->success(null, 'Code sent');
    }

    // Password Reset - Simplified
    public function requestPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = User::where('email', $request->email)->first();
        $code = rand(100000, 999999);
        
        // Store code in cache for 1 hour
        cache()->put('reset_code_' . $user->email, $code, 3600);
        
        // Log the code (in production, send email)
        Mail::raw("Your password reset code is: $code", function ($message) use ($user) {
            $message->to($user->email)->subject('Password Reset Code');
        });

        return $this->success(null, 'Reset code sent');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $storedCode = cache()->get('reset_code_' . $request->email);
        
        if (!$storedCode || $storedCode !== $request->code) {
            return $this->error('Invalid reset code', 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        cache()->forget('reset_code_' . $request->email);
        $user->tokens()->delete(); // Revoke all tokens

        return $this->success(null, 'Password reset successfully');
    }

    // Helper Methods
    private function sendVerificationCode($user)
    {
        $code = rand(100000, 999999);
        
        // Store in cache for 10 minutes
        cache()->put('verify_code_' . $user->id, (string)$code, 600);
        
        // Send email (will log in development)
        Mail::raw("Your verification code is: $code", function ($message) use ($user) {
            $message->to($user->email)->subject('Email Verification Code');
        });
    }

    private function getRedirectPath($user)
    {
        return match($user->role) {
            'admin' => '/admin',
            'organizer' => '/organizer/dashboard',
            'user' => '/my-account',
            default => '/'
        };
    }
}