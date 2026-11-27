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

        // HSTS tells browsers to always use HTTPS. It is only set in production.
        if ($request->isSecure() && config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $header = config('security.csp.enforce', false)
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        $response->headers->set($header, $this->policy(
            $nonce,
            $request->routeIs('permits.verify.form', 'admin.document-authentication.index')
                || ($request->routeIs('permits.verify.upload') && $response->getStatusCode() === 422),
        ));

        return $response;
    }

    private function policy(string $nonce, bool $allowLocalPdfPreview): string
    {
        $directives = config('security.csp.directives', []);
        if ($allowLocalPdfPreview) {
            $directives['frame-src'][] = 'blob:';
        }

        $policy = [];

        foreach ($directives as $name => $sources) {
            $renderedSources = array_map(
                fn (string $source): string => str_replace('{nonce}', $nonce, $source),
                $sources
            );
            $policy[] = $name.' '.implode(' ', $renderedSources);
        }

        return implode('; ', $policy);
    }
}
