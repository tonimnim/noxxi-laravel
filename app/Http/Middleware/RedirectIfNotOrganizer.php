<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotOrganizer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            return redirect('/');
        }

        // Check if user has organizer role
        if (auth()->user()->role !== 'organizer') {
            return redirect('/');
        }

        // Check if organizer profile exists
        if (! auth()->user()->organizer) {
            return redirect('/');
        }

        return $next($request);
    }
}
