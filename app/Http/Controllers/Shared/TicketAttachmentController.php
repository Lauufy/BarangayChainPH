<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function __invoke(Request $request, TicketMessage $message): StreamedResponse
    {
        $viewer = $request->user();
        abort_if($viewer->isStaff() && ! $viewer->hasPermission('tickets.reply'), 403);
        abort_if($viewer->isCitizen() && $viewer->id !== $message->ticket->user_id, 404);
        abort_unless($message->attachment_path, 404);

        return Storage::disk('supabase')->download($message->attachment_path, $message->attachment_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
