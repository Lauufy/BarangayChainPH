<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

// Adds browser security headers to every response.
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // A one-time code per page load. Only scripts carrying it are trusted.
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp-nonce', $nonce);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        // These headers are safe everywhere and block common attacks.
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(), microphone=()');

        // HSTS tells browsers to always use HTTPS. Never on local, or you get stuck on https.
        if ($request->isSecure() && ! app()->isLocal()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // The content policy is skipped on local so the Vite dev server keeps working.
        $mode = env('CSP_MODE', 'report-only');

        if (! app()->isLocal() && $mode !== 'off') {
            $header = $mode === 'enforce'
                ? 'Content-Security-Policy'
                : 'Content-Security-Policy-Report-Only';

            $response->headers->set($header, $this->policy($nonce));
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net",
            "img-src 'self' data: blob: https://*.supabase.co",
            "media-src 'self' blob:",
            "connect-src 'self' https://*.supabase.co",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
