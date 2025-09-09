<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheStaticPages
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Add cache headers for static pages (cache for 1 hour)
        $response->header('Cache-Control', 'public, max-age=3600');
        $response->header('Vary', 'Accept-Encoding');
        
        return $response;
    }
}
