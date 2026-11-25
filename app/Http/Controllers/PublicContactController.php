<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Notifications\CitizenSubmissionReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PublicContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'service' => [
                'required',
                'string',
                Rule::in([
                    'Barangay Clearance',
                    'Certificate of Residency',
                    'Certificate of Indigency',
                    'Document Verification',
                    'Other',
                ]),
            ],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($data) {
            $inquiry = ContactMessage::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'service' => $data['service'],
                'subject' => $data['subject'],
                'body' => $data['message'],
                'status' => ContactMessage::NEW,
            ]);

            (new CitizenSubmissionReceived(
                'New email inquiry',
                $inquiry->subject,
                route('admin.contact-messages.show', $inquiry),
            ))->sendToTicketInbox();
        });

        return redirect()->to(route('home').'#contact')
            ->with('success', 'Thank you. Your message has been received, and we will reply by email.');
    }
}
