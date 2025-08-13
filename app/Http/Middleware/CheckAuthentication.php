<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // If user is not authenticated, redirect to login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Update last activity to keep session alive
        session()->put('last_activity', now());
        
        // Update user's last active timestamp
        Auth::user()->updateLastActive();

        return $next($request);
    }
}