<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostTicketMessage
{
    public function __construct(private SupabaseStorage $storage) {}

    public function handle(Request $request, Ticket $ticket): void
    {
        $data = $request->validate([
            'body' => ['required_without:attachment', 'nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt', 'extensions:jpg,jpeg,png,webp,pdf,doc,docx,txt', 'max:5120'],
        ]);

        $file = $request->file('attachment');
        $path = $file ? $this->storage->upload($file, 'ticket-attachments') : null;

        try {
            DB::transaction(function () use ($ticket, $request, $data, $file, $path) {
                if ($ticket->exists) {
                    $locked = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
                    abort_unless($locked->status === Ticket::OPEN, 403, 'This ticket is closed.');
                } else {
                    $ticket->save();
                }

                $ticket->messages()->create([
                    'user_id' => $request->user()->id,
                    'body' => $data['body'] ?? '',
                    'attachment_path' => $path,
                    'attachment_name' => $file?->getClientOriginalName(),
                ]);
            });
        } catch (Throwable $exception) {
            $this->storage->delete($path);
            throw $exception;
        }
    }
}
