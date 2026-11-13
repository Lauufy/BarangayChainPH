<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseStorage;
use App\Support\Phone;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    private const UPLOAD_ERROR = 'The photo could not be uploaded. Please try again.';

    // Same folder the admin profile page uses.
    private const PHOTO_FOLDER = 'admin-profile-photos';

    public function __construct(private SupabaseStorage $avatars) {}

    /**
     * Display user accounts.
     */
    public function index(): View
    {
        $users = User::with(['citizenProfile', 'employeeProfile'])
            ->latest()
            ->paginate(10);
        $accountCounts = [
            'total' => User::count(),
            'staff' => User::where('role', 'staff')->count(),
            'admin' => User::where('role', 'admin')->count(),
        ];

        return view('admin.users.index', compact('users', 'accountCounts'));
    }

    /**
     * Create a staff, admin or citizen account.
     */
    public function store(Request $request): RedirectResponse
    {

        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['staff', 'admin', 'citizen'])],
            'department' => ['required_if:role,staff,admin', 'nullable', 'string', 'max:255'],
            'position' => ['required_if:role,staff,admin', 'nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^\+639\d{9}$/', 'unique:users,phone'],
            'office' => ['nullable', 'string', 'max:255'],
            'date_hired' => ['nullable', 'date', 'before_or_equal:today'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'max:16',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        // Only staff/admin get a photo here. Upload first; it is removed again if the save fails.
        $avatarPath = null;
        if ($validated['role'] !== 'citizen' && $request->hasFile('avatar')) {
            try {
                $avatarPath = $this->avatars->upload($request->file('avatar'), self::PHOTO_FOLDER);
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors(['avatar' => self::UPLOAD_ERROR]);
            }
        }

        try {
            DB::transaction(function () use ($validated, $avatarPath) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => $validated['password'],
                    'role' => $validated['role'],
                ]);

                // New staff start with the default access. An admin can change it later.
                if ($validated['role'] === 'staff') {
                    $user->forceFill(['permissions' => config('permissions.staff_defaults')])->save();
                }

                if ($validated['role'] !== 'citizen') {
                    $user->employeeProfile()->create([
                        'employee_id' => 'BSLCNPH-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
                        'department' => $validated['department'],
                        'position' => $validated['position'],
                        'office' => $validated['office'] ?? null,
                        'date_hired' => $validated['date_hired'] ?? null,
                        'photo_path' => $avatarPath,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            $this->avatars->delete($avatarPath);

            throw $e;
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'The account was created successfully.');
    }

    /**
     * Update an account (staff, admin or citizen).
     */
    /**
     * Update an account (staff, admin or citizen).
     */
    public function update(Request $request, User $user): RedirectResponse
    {

        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        // Named error bag so the Edit popup (not the Create popup) reopens on errors.
        $validated = $request->validateWithBag('updateUser', [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in($user->role === 'citizen' ? ['citizen'] : ['staff', 'admin'])],
            'department' => ['required_if:role,staff,admin', 'nullable', 'string', 'max:255'],
            'position' => ['required_if:role,staff,admin', 'nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^\+639\d{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'office' => ['nullable', 'string', 'max:255'],
            'date_hired' => ['nullable', 'date', 'before_or_equal:today'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            // Leave blank to keep the current password.
            'password' => [
                'nullable',
                'string',
                'confirmed',
                'max:16',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        // Citizen details (saved to citizen_profiles)
        $citizen = ($user->role !== 'citizen' || ! $user->citizenProfile) ? [] : $request->validateWithBag('updateUser', [
            'civil_status' => ['required', Rule::in(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'])],
            'religion' => ['nullable', 'max:100'],
            'occupation' => ['nullable', 'max:255'],
            'house_number' => ['nullable', 'max:50'],
            'barangay' => ['required', 'max:255'],
            'city_municipality' => ['required', 'max:255'],
            'province' => ['required', 'max:255'],
            'zip_code' => ['required', 'max:10'],
        ], ['phone.regex' => 'Enter a valid phone number.']);

        $roleChanged = $validated['role'] !== $user->role;

        if ($roleChanged) {
            if ($request->user()->is($user)) {
                return back()
                    ->withInput()
                    ->withErrors(['role' => 'You cannot change your own role.'], 'updateUser');
            }

            if ($user->role === 'admin' && $this->adminCount() <= 1) {
                return back()
                    ->withInput()
                    ->withErrors(['role' => 'The last administrator cannot be demoted.'], 'updateUser');
            }
        }

        // Photo (staff/admin only): upload the new one first, remove the old one after the save succeeded.
        $isEmployee = $validated['role'] !== 'citizen';
        $oldAvatar = $user->employeeProfile?->photo_path;
        $newAvatar = null;

        if ($isEmployee && $request->hasFile('avatar')) {
            try {
                $newAvatar = $this->avatars->upload($request->file('avatar'), self::PHOTO_FOLDER);
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors(['avatar' => self::UPLOAD_ERROR], 'updateUser');
            }
        }

        $removeAvatar = $isEmployee && ! $newAvatar && $request->boolean('remove_avatar');

        try {
            DB::transaction(function () use ($validated, $citizen, $user, $roleChanged, $isEmployee, $newAvatar, $removeAvatar) {
                $attributes = [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'role' => $validated['role'],
                ];

                if (($validated['phone'] ?? null) !== $user->phone) {
                    $attributes['phone_verified_at'] = null;
                }

                if (! empty($validated['password'])) {
                    $attributes['password'] = $validated['password'];
                }

                $user->update($attributes);

                if ($roleChanged) {
                    // Staff get the default access; admins don't use the permission list.
                    $user->forceFill([
                        'permissions' => $validated['role'] === 'staff'
                            ? config('permissions.staff_defaults')
                            : null,
                    ])->save();
                }

                if ($isEmployee) {
                    $profile = [
                        'department' => $validated['department'],
                        'position' => $validated['position'],
                        'office' => $validated['office'] ?? null,
                        'date_hired' => $validated['date_hired'] ?? null,
                    ];

                    if ($newAvatar) {
                        $profile['photo_path'] = $newAvatar;
                    } elseif ($removeAvatar) {
                        $profile['photo_path'] = null;
                    }

                    $user->employeeProfile()->updateOrCreate(['user_id' => $user->id], $profile);
                }

                if ($citizen) {
                    // Same address format as the citizen's own profile page
                    $citizen['address'] = implode(', ', array_filter([
                        $citizen['house_number'] ?? null,
                        $citizen['barangay'],
                        $citizen['city_municipality'],
                        $citizen['province'],
                        $citizen['zip_code'],
                    ]));

                    $user->citizenProfile?->update($citizen);
                }
            });
        } catch (\Throwable $e) {
            $this->avatars->delete($newAvatar);

            throw $e;
        }

        if ($newAvatar || $removeAvatar) {
            $this->avatars->delete($oldAvatar);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'The account was updated successfully.');
    }

    /**
     * Delete an account.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['account' => 'You cannot delete your own account.']);
        }

        if ($user->role === 'admin' && $this->adminCount() <= 1) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['account' => 'The last administrator cannot be deleted.']);
        }

        // Remember the photos before the profiles are deleted.
        $photos = [$user->employeeProfile?->photo_path, $user->citizenProfile?->photo_path];

        try {
            DB::transaction(function () use ($user) {
                $user->employeeProfile()->delete();
                $user->citizenProfile()->delete();
                $user->delete();
            });
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('admin.users.index')
                ->withErrors([
                    'account' => 'This account still has related records (requests, tickets, etc.), so it cannot be deleted yet.',
                ]);
        }

        foreach ($photos as $photo) {
            $this->avatars->delete($photo);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'The account was deleted successfully.');
    }

    public function permissions(User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        return redirect()->route('admin.users.index');
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        // Named error bag so the Permissions popup (not the Create popup) reopens on errors.
        $data = $request->validateWithBag('updatePermissions', [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(config('permissions.list')))],
        ]);

        $user->forceFill(['permissions' => array_values($data['permissions'] ?? [])])->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'The staff permissions were updated.');
    }

    private function adminCount(): int
    {
        return User::where('role', 'admin')->count();
    }
}
