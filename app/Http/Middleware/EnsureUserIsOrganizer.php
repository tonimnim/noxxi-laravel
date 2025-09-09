<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsOrganizer
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isOrganizer()) {
            return $this->forbidden('Access restricted to organizers only');
        }

        if ($request->user()->organizer && !$request->user()->organizer->is_verified) {
            return $this->forbidden('Your organizer account is pending verification');
        }

        return $next($request);
    }
}