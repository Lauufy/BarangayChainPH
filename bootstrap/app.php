<?php

use App\Http\Middleware\EnsureIdentityVerified;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware): void {
        $proxies = env('TRUSTED_PROXIES', '');

        $middleware->trustProxies(
            at: $proxies === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $proxies)))),
        );

        $middleware->redirectUsersTo(fn (Request $request) => match ($request->user()?->role) {
            'admin', 'staff' => route('admin.dashboard'),
            'citizen' => route('citizen.dashboard'),
            default => route('home'),
        });

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'identity.verified' => EnsureIdentityVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->create();
