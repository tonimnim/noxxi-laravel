<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers to the response.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection (though modern browsers have this by default)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Force HTTPS in production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy - adjust based on your needs
        $csp = $this->buildContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);

        // Referrer Policy - controls how much referrer information should be included
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature Policy)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Remove X-Powered-By header if present
        $response->headers->remove('X-Powered-By');

        // Add custom security header to identify protected responses
        $response->headers->set('X-Security-Headers', 'enabled');

        return $response;
    }

    /**
     * Build Content Security Policy based on environment
     */
    private function buildContentSecurityPolicy(): string
    {
        $policies = [];

        // Default source - only allow from same origin
        $policies[] = "default-src 'self'";

        // Scripts - allow self and inline scripts (needed for Laravel/Vue)
        // In production, consider using nonces or hashes instead of 'unsafe-inline'
        if (app()->environment('local', 'development')) {
            // More permissive in development for hot reload
            $policies[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:* ws://localhost:* http://127.0.0.1:* ws://127.0.0.1:* http://127.0.0.1:5173 ws://127.0.0.1:5173";
        } else {
            // Stricter in production
            $policies[] = "script-src 'self' 'unsafe-inline'";
        }

        // Styles - allow self and inline styles (needed for Vue components)
        if (app()->environment('local', 'development')) {
            $policies[] = "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net http://localhost:* http://127.0.0.1:* http://127.0.0.1:5173";
        } else {
            $policies[] = "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net";
        }

        // Images - allow from self, data URIs, blob URLs (for file uploads), and HTTPS
        $policies[] = "img-src 'self' data: blob: https:";

        // Fonts - allow from self, Google Fonts, and Bunny Fonts
        if (app()->environment('local', 'development')) {
            // Include Vite dev server in development
            $policies[] = "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net http://localhost:* http://127.0.0.1:* http://127.0.0.1:5173";
        } else {
            $policies[] = "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net";
        }

        // Forms - only submit to same origin
        $policies[] = "form-action 'self'";

        // Frame ancestors - prevent embedding (same as X-Frame-Options)
        $policies[] = "frame-ancestors 'none'";

        // Base URI - restrict base tag
        $policies[] = "base-uri 'self'";

        // Connect - for API calls (adjust for your API endpoints)
        if (app()->environment('local', 'development')) {
            $policies[] = "connect-src 'self' http://localhost:* ws://localhost:* http://127.0.0.1:* ws://127.0.0.1:* http://127.0.0.1:5173 ws://127.0.0.1:5173";
        } else {
            // Add your production API domains here
            $policies[] = "connect-src 'self' https://api.noxxi.com";
        }

        // Media - if you have audio/video
        $policies[] = "media-src 'self'";

        // Object - block plugins like Flash
        $policies[] = "object-src 'none'";

        // Worker - for service workers
        $policies[] = "worker-src 'self'";

        // Manifest - for PWA manifest
        $policies[] = "manifest-src 'self'";

        return implode('; ', $policies);
    }
}
