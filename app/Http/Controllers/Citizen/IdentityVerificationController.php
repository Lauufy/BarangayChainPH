<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use Illuminate\Http\Request;

class IdentityVerificationController extends Controller
{
    // Accepted government IDs
    private array $documentTypes = [
        'Philippine National ID', "Driver's License", 'Passport', 'UMID',
        'Postal ID', "Voter's ID", 'PRC ID', 'Barangay ID',
    ];

    // Show Step 1, Step 2, or the current status
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->citizenProfile;

        // Step 2 opens only when Step 1 is complete
        $step = ($request->step == 2 && $profile?->hasCompleteDetails()) ? 2 : 1;

        return view('citizen.identity-verification', [
            'profile' => $profile,
            'verification' => $user->latestIdentityVerification,
            'status' => $user->identityStatus(),
            'step' => $step,
            'documentTypes' => $this->documentTypes,
        ]);
    }

    // Step 1: save the details, then go to Step 2
    public function storeDetails(Request $request)
    {
        if ($this->isLocked($request->user())) {
            return redirect()->route('citizen.identity-verification');
        }

        $data = $request->validate([
            'phone' => 'required|max:15|regex:/^[0-9+\-\s()]{7,20}$/',
            'date_of_birth' => 'required|date|before:today|after:1900-01-01',
            'gender' => 'required|in:Male,Female',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
            'nationality' => 'required|max:100',
            'place_of_birth' => 'required|max:255',
            'religion' => 'nullable|max:100',
            'occupation' => 'nullable|max:255',
            'house_number' => 'nullable|max:50',
            'barangay' => 'required|max:255',
            'city_municipality' => 'required|max:255',
            'province' => 'required|max:255',
            'zip_code' => 'required|max:10',
        ], [
            'phone.regex' => 'Enter a valid phone number.',
        ]);

        // Join the parts into the one-line "address" column
        $data['address'] = implode(', ', array_filter([
            $data['house_number'] ?? null, $data['barangay'],
            $data['city_municipality'], $data['province'], $data['zip_code'],
        ]));

        $request->user()->citizenProfile()->updateOrCreate([], $data);

        return redirect()->route('citizen.identity-verification', ['step' => 2]);
    }

    // Step 2: upload the ID and submit for review
    public function storeDocument(Request $request)
    {
        $user = $request->user();

        if (! $user->citizenProfile?->hasCompleteDetails()) {
            return redirect()->route('citizen.identity-verification')
                ->withErrors(['details' => 'Please complete your details first.']);
        }

        if ($this->isLocked($user)) {
            return redirect()->route('citizen.identity-verification');
        }

        $request->validate([
            'document_type' => 'required|in:'.implode(',', $this->documentTypes),
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'document.mimes' => 'The ID must be a JPG, PNG, or PDF file.',
            'document.max' => 'The ID must not be larger than 5 MB.',
        ]);

        // Save the file in private storage (no public link)
        $file = $request->file('document');

        $user->identityVerifications()->create([
            'status' => IdentityVerification::PENDING,
            'document_type' => $request->document_type,
            'document_path' => $file->store('identity-documents/'.$user->id, 'local'),
            'document_original_name' => $file->getClientOriginalName(),
            'submitted_at' => now(),
        ]);

        return redirect()->route('citizen.identity-verification')
            ->with('success', 'Your application was submitted. Please wait for review.');
    }

    // Only the owner can open their uploaded ID
    public function document(Request $request, IdentityVerification $verification)
    {
        abort_unless(
            $verification->user_id === $request->user()->id && is_file($verification->filePath()),
            404
        );

        return response()->file($verification->filePath());
    }

    // Pending or verified applications cannot be changed or resubmitted
    private function isLocked($user): bool
    {
        return in_array($user->identityStatus(), ['pending', 'verified']);
    }
}
