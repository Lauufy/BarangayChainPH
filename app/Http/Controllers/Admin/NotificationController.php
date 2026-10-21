<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->to($this->notificationPath(
            $notification->data['url'] ?? null,
            route('admin.notifications.index', [], false),
        ));
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back();
    }

    private function notificationPath(mixed $url, string $fallback): string
    {
        $path = is_string($url) ? parse_url($url, PHP_URL_PATH) : null;

        return is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//')
            ? $path
            : $fallback;
    }
}
