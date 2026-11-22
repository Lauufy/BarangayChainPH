<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\UpdatesTwoFactor;
use App\Http\Controllers\Controller;
use App\Services\SupabaseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Manages the signed-in staff member's profile and private photo. */
class ProfileController extends Controller
{
    use UpdatesTwoFactor;

    /** Fields that use a list plus a "Specify ..." box for "Others". */
    private const CHOICE_FIELDS = ['department', 'position', 'office'];

    public function __construct(private SupabaseStorage $supabaseStorage) {}

    public function show(Request $request): View
    {
        $user = $request->user()->load('employeeProfile');
        $profile = $user->employeeProfile;

        $errors = $request->session()->get('errors');
        $bag = $errors?->getBag('default');
        $old = $request->session()->getOldInput('_form');

        // Reopen the details form in edit mode when saving it failed.
        $editing = $old === 'details' && $bag?->hasAny([
            'name',
            'email',
            'phone',
            'employment_type',
            'department',
            'department_other',
            'position',
            'position_other',
            'office',
            'office_other',
            'date_hired',
        ]);

        $pageErrors = collect($bag?->getMessages() ?? [])
            ->except(['photo', 'two_factor_enabled'])
            ->flatten();

        return view('admin.profile.show', [
            'user' => $user,
            'profile' => $profile,
            'editing' => (bool) $editing,
            'pageErrors' => $pageErrors,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $changingPassword = $request->input('_form') === 'password';

        // Each form on the page sends only its own fields, so "sometimes" keeps
        // the others (e.g. employment details, when only the password changes) untouched.
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],

            // Employment details (the employee ID is not editable here)
            'employment_type' => ['sometimes', 'required', Rule::in(['full_time', 'part_time', 'contract'])],
            'department' => ['sometimes', 'required', 'string', 'max:255'],
            'department_other' => ['nullable', 'required_if:department,others', 'string', 'max:255'],
            'position' => ['sometimes', 'required', 'string', 'max:255'],
            'position_other' => ['nullable', 'required_if:position,others', 'string', 'max:255'],
            'office' => ['sometimes', 'nullable', 'string', 'max:255'],
            'office_other' => ['nullable', 'required_if:office,others', 'string', 'max:255'],
            'date_hired' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],

            'current_password' => [$changingPassword ? 'required' : 'nullable', 'current_password'],
            'password' => [
                $changingPassword ? 'required' : 'nullable',
                'confirmed',
                'max:16',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        // "Others" picked: save the typed text instead of the word "others".
        foreach (self::CHOICE_FIELDS as $field) {
            if (($validated[$field] ?? null) === 'others') {
                $validated[$field] = trim((string) $request->input($field.'_other'));
            }
        }

        $user->forceFill(Arr::only($validated, ['name', 'email', 'phone']));

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $employment = Arr::only($validated, ['employment_type', 'department', 'position', 'office', 'date_hired']);
        $employeeProfile = $user->employeeProfile;

        DB::transaction(function () use ($user, $employeeProfile, $employment) {
            $user->save();

            if ($employment !== [] && $employeeProfile) {
                $employeeProfile->update($employment);
            }
        });

        return redirect()
            ->route('admin.profile')
            ->with('success', $changingPassword
                ? 'Your password was changed successfully.'
                : 'Your profile was updated successfully.');
    }

    public function photo(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'photo.max' => 'The photo must not be larger than 2 MB.',
        ]);

        $user = $request->user();
        $employeeProfile = $user->employeeProfile;

        abort_unless($employeeProfile, 403);
        $oldPath = $employeeProfile->photo_path;

        $employeeProfile->update([
            'photo_path' => $request->file('photo')->store('admin-profile-photos', 'supabase'),
        ]);

        if ($oldPath) {
            Storage::disk('supabase')->delete($oldPath);
        }

        return redirect()
            ->route('admin.profile')
            ->with('success', 'Your profile photo was updated.');
    }

    public function showPhoto(Request $request): RedirectResponse
    {
        $path = $request->user()->employeeProfile?->photo_path;

        abort_unless($path, 404);

        return redirect()->away($this->supabaseStorage->temporaryUrl($path, now()->addMinutes(10)));
    }
}
