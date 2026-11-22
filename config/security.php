<?php

return [
    'csp' => [
        'enforce' => (bool) env('CSP_ENFORCE', false),
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => [
                "'self'",
                "'nonce-{nonce}'",
                'https://www.google.com',
                'https://www.gstatic.com',
                'https://www.tuqlas.com',
                'https://cdn.jsdelivr.net',
            ],
            'style-src' => [
                "'self'",
                "'unsafe-inline'",
                'https://fonts.googleapis.com',
                'https://fonts.bunny.net',
            ],
            'font-src' => [
                "'self'",
                'data:',
                'https://fonts.gstatic.com',
                'https://fonts.bunny.net',
            ],
            'img-src' => ["'self'", 'data:', 'blob:', 'https://*.supabase.co'],
            'media-src' => ["'self'", 'blob:', 'https://storage.googleapis.com'],
            'connect-src' => [
                "'self'",
                'https://*.supabase.co',
                'https://www.tuqlas.com',
                'https://cdn.jsdelivr.net',
                'https://storage.googleapis.com',
            ],
            'frame-src' => [
                'https://www.google.com',
                'https://maps.google.com',
                'https://www.google.com/maps',
                'https://www.google.com/recaptcha/',
            ],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
        ],
    ],
];
