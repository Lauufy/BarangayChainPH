<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $verified = $user->isIdentityVerified();

        return view('citizen.profile', [
            'user' => $user,
            'profile' => $user->citizenProfile,
            'status' => $user->identityStatus(),
            'verified' => $verified,
            // Stay unlocked when a save failed, so the errors are visible
            'editing' => $verified && session()->hasOldInput(),
        ]);
    }

    // Save the editable details
    public function update(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|max:30|regex:/^[0-9+\-\s()]{7,20}$/',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
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

        $data['address'] = implode(', ', array_filter([
            $data['house_number'] ?? null, $data['barangay'],
            $data['city_municipality'], $data['province'], $data['zip_code'],
        ]));

        // Update the model (not the relation query) so the religion encryption still applies
        $request->user()->citizenProfile->update($data);

        return redirect()->route('citizen.profile')->with('success', 'Your profile was updated.');
    }

    // Save a new profile photo
    public function photo(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'photo.max' => 'The photo must not be larger than 2 MB.',
        ]);

        $user = $request->user();
        $oldPath = $user->citizenProfile?->photo_path;

        $path = $request->file('photo')->store('profile-photos', 'local');
        $user->citizenProfile()->update(['photo_path' => $path]);

        // Remove the old photo after the new one is saved
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('citizen.profile')->with('success', 'Your photo was updated.');
    }

    // Show the user's own photo
    public function showPhoto(Request $request)
    {
        $path = $request->user()->citizenProfile?->photo_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->response($path);
    }
}