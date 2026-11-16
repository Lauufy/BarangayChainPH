<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index', [
            'citizenMessages' => [
                [
                    'id' => 1,
                    'name' => 'Maria Santos',
                    'initials' => 'MS',
                    'subject' => 'Barangay Clearance',
                    'preview' => 'Thank you for your assistance!',
                    'time' => '9:42 AM',
                    'unread' => true,
                    'pending' => true,
                    'phone' => '09XX XXX 1234',
                    'email' => 'maria@example.com',
                    'messages' => [
                        ['from' => 'citizen', 'text' => 'Good morning! May I ask about the requirements for a barangay clearance?', 'time' => '9:30 AM'],
                        ['from' => 'staff', 'text' => 'Good morning! Please prepare a valid ID and the required supporting documents.', 'time' => '9:35 AM'],
                        ['from' => 'citizen', 'text' => 'Thank you for your assistance!', 'time' => '9:42 AM'],
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Juan Dela Cruz',
                    'initials' => 'JD',
                    'subject' => 'Permit Application',
                    'preview' => 'May I follow up on my application?',
                    'time' => 'Yesterday',
                    'unread' => false,
                    'pending' => true,
                    'phone' => '09XX XXX 5678',
                    'email' => 'juan@example.com',
                    'messages' => [
                        ['from' => 'citizen', 'text' => 'Hello, I would like to follow up on my permit application.', 'time' => 'Yesterday, 2:15 PM'],
                        ['from' => 'staff', 'text' => 'Thank you for contacting us. Your inquiry is being reviewed.', 'time' => 'Yesterday, 2:30 PM'],
                    ],
                ],
                [
                    'id' => 3,
                    'name' => 'Ana Reyes',
                    'initials' => 'AR',
                    'subject' => 'Certificate Request',
                    'preview' => 'Thank you! I received the information.',
                    'time' => 'Oct 8',
                    'unread' => false,
                    'pending' => false,
                    'phone' => '09XX XXX 9012',
                    'email' => 'ana@example.com',
                    'messages' => [
                        ['from' => 'citizen', 'text' => 'Good afternoon. I would like to inquire about requesting a certificate.', 'time' => 'Oct 8, 10:10 AM'],
                        ['from' => 'staff', 'text' => 'Good afternoon! Kindly prepare your valid ID and supporting documents.', 'time' => 'Oct 8, 10:20 AM'],
                        ['from' => 'citizen', 'text' => 'Thank you! I received the information.', 'time' => 'Oct 8, 10:25 AM'],
                    ],
                ],
            ],
        ]);
    }

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
