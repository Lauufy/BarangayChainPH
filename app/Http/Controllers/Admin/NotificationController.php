<?php

namespace App\Http\Controllers\Admin;

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

        return view('admin.notifications.index', [
            'notifications' => $query->paginate(10)->withQueryString(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'filter' => $filter,
        ]);
    }

    // Mark ONE as read. Runs only when "Mark as read" (or the bell's
    // dismiss button) is pressed. Opening a notification never calls this.
    public function read(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
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
