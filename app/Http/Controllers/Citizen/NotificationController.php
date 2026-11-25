<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // List page (All / Unread, 10 per page).
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $query = $filter === 'unread'
            ? $user->unreadNotifications()
            : $user->notifications();

        return view('citizen.notifications.index', [
            'notifications' => $query->paginate(10)->withQueryString(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'filter' => $filter,
        ]);
    }

    public function read(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->boolean('open')) {
            return redirect()->to(($notification->data['url'] ?? null) ?: route('citizen.notifications'));
        }

        return $request->expectsJson()
            ? response()->json([
                'ok' => true,
                'unreadCount' => $request->user()->unreadNotifications()->count(),
            ])
            : back();
    }

    // Mark ALL unread as read.
    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back();
    }
}
