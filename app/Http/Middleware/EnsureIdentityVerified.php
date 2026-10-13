<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdentityVerified
{
    // Only used inside the citizen route group (already logged in as a citizen)
    public function handle(Request $request, Closure $next): Response
    {
        // Verified citizens can continue
        if ($request->user()->isIdentityVerified()) {
            return $next($request);
        }

        // Direct requests (no browser) get a plain 403
        if ($request->expectsJson()) {
            abort(403, 'Identity verification is required.');
        }

        // Browser requests go to the verification page
        return redirect()->route('citizen.identity-verification')
            ->with('warning', 'Please verify your identity first.');
    }
}