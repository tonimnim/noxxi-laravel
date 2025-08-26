<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionBridgeController extends Controller
{
    /**
     * Bridge API token authentication to web session
     */
    public function bridge(Request $request)
    {
        // Get the token from the request
        $token = $request->bearerToken() ?? $request->get('token');

        if (! $token) {
            return redirect('/login')->with('error', 'No authentication token provided');
        }

        try {
            // Authenticate using the API guard to get the user
            $user = auth('api')->user();

            if (! $user) {
                // Try to get user from token directly
                $user = \Laravel\Passport\Token::where('id', $token)->first()?->user;

                if (! $user) {
                    return redirect('/login')->with('error', 'Invalid authentication token');
                }
            }

            // Check if email is verified
            if (! $user->email_verified_at) {
                return redirect('/email/verify');
            }

            // Log the user into the web session
            Auth::guard('web')->login($user, true);

            // Redirect based on role
            return match ($user->role) {
                'admin' => redirect('/admin'),
                'organizer' => redirect('/organizer/dashboard'),
                'user' => redirect('/'),
                default => redirect('/')
            };

        } catch (\Exception $e) {
            \Log::error('Session bridge error: '.$e->getMessage());

            return redirect('/login')->with('error', 'Authentication failed');
        }
    }

    /**
     * Handle post-verification redirect
     */
    public function postVerification(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/login');
        }

        // Ensure user is logged into web session
        if (! Auth::guard('web')->check()) {
            Auth::guard('web')->login($user, true);
        }

        // Redirect based on role
        return match ($user->role) {
            'admin' => redirect('/admin'),
            'organizer' => redirect('/organizer/dashboard'),
            'user' => redirect('/user'),
            default => redirect('/')
        };
    }
}
