<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistory
{
    /**
     * Tell the browser not to cache the response, so the Back button
     * cannot show a protected page after the user has logged out.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Streamed and binary responses (file downloads) keep their own headers
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }
}