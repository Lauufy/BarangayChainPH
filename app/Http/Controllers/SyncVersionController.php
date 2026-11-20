<?php

namespace App\Http\Controllers;

use App\Support\SyncVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tiny endpoint polled by the live-sync script to learn whether the page data changed.
 */
class SyncVersionController extends Controller
{
    public function __invoke(Request $request, string $scope): JsonResponse
    {
        $user = $request->user();

        $allowed = match ($scope) {
            'admin' => $user->isAdmin() || $user->isStaff(),
            'citizen' => $user->isCitizen(),
            default => false,
        };

        abort_unless($allowed, 403);

        return response()
            ->json(['version' => SyncVersion::current($user->id, $scope)])
            ->header('Cache-Control', 'no-store');
    }
}