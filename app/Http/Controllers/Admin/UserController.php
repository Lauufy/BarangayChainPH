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
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    private const UPLOAD_ERROR = 'The photo could not be uploaded. Please try again.';

    // Same folder the admin profile page uses.
    private const PHOTO_FOLDER = 'admin-profile-photos';

    public function __construct(private SupabaseStorage $avatars) {}

    public function index(): View
    {
        $with = ['employeeProfile', 'citizenProfile'];

        // All tab: everyone, newest first
        $all = User::with($with)->latest()
            ->paginate(10, ['*'], 'all_page')->withQueryString();

        // Employees tab: staff + administrators
        $users = User::with($with)->whereIn('role', ['staff', 'admin'])->latest()
            ->paginate(10, ['*'], 'staff_page')->withQueryString();

        // Citizens tab
        $citizens = User::with($with)->where('role', 'citizen')->latest()
            ->paginate(10, ['*'], 'citizen_page')->withQueryString();

        // One grouped query for the stat cards
        $roleStats = User::query()
            ->selectRaw('role, count(*) as total, max(created_at) as last_created')
            ->groupBy('role')
            ->get()
            ->keyBy('role');

        $accountCounts = [
            'total' => $roleStats->sum('total'),
            'staff' => $roleStats['staff']->total ?? 0,
            'admin' => $roleStats['admin']->total ?? 0,
        ];

        $accountLatest = [
            'total' => $roleStats->max('last_created'),
            'staff' => $roleStats['staff']->last_created ?? null,
            'admin' => $roleStats['admin']->last_created ?? null,
        ];

        return view('admin.users.index', compact('all', 'users', 'citizens', 'accountCounts', 'accountLatest'));
    }

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
                        'employee_id' => 'BSLCNPH-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
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

    public function export(Request $request): StreamedResponse
    {
        $tab = in_array($request->query('users_tab'), ['employees', 'citizens'], true)
            ? $request->query('users_tab')
            : 'all';

        $query = User::with(['employeeProfile', 'citizenProfile']);

        if ($tab === 'employees') {
            $query->whereIn('role', ['staff', 'admin']);
        } elseif ($tab === 'citizens') {
            $query->where('role', 'citizen');
        }

        // Stops Excel from running cells that start with = + - @ as formulas
        $safe = fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;

        return response()->streamDownload(function () use ($query, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads accents correctly

            fputcsv($out, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Role',
                'Date Created',
                'Employee ID',
                'Department',
                'Position',
                'Office',
                'Date Hired',
                'Gender',
                'Date of Birth',
                'Civil Status',
                'Nationality',
                'Place of Birth',
                'Religion',
                'Occupation',
                'House Number',
                'Barangay',
                'City/Municipality',
                'Province',
                'ZIP Code',
                'Full Address',
            ]);

            $query->chunkById(200, function ($users) use ($out, $safe) {
                foreach ($users as $u) {
                    $p = $u->employeeProfile;
                    $c = $u->citizenProfile;

                    fputcsv($out, array_map($safe, [
                        $u->id,
                        $u->name,
                        $u->email,
                        $u->phone,
                        $u->role,
                        $u->created_at->format('Y-m-d'),
                        $p?->employee_id,
                        $p?->department,
                        $p?->position,
                        $p?->office,
                        $p?->date_hired?->format('Y-m-d'),
                        $c?->gender,
                        $c?->date_of_birth?->format('Y-m-d'),
                        $c?->civil_status,
                        $c?->nationality,
                        $c?->place_of_birth,
                        $c?->religion,
                        $c?->occupation,
                        $c?->house_number,
                        $c?->barangay,
                        $c?->city_municipality,
                        $c?->province,
                        $c?->zip_code,
                        $c?->full_address,
                    ]));
                }
            });

            fclose($out);
        }, "users-{$tab}-" . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
