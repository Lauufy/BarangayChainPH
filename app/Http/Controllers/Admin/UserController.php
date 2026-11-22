<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\ServiceRequest;
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

    private const STATUSES = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'];

    private const EMPLOYMENT_TYPES = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract'];

    private const STATUS_VARIANTS = ['active' => 'success', 'inactive' => 'neutral', 'suspended' => 'danger'];

    public function __construct(
        private SupabaseStorage $avatars,
        private UsersExport $usersExport,
    ) {}

    public function index(Request $request): View
    {
        $view = in_array($request->input('users_tab'), ['employees', 'citizens'], true)
            ? $request->input('users_tab')
            : 'all';
        // All tab: everyone, newest first
        $all = $this->usersExport->queryForTab('all')
            ->paginate(10, ['*'], 'all_page')->appends($request->except('search'));

        // Employees tab: staff + administrators
        $users = $this->usersExport->queryForTab('employees')
            ->paginate(10, ['*'], 'staff_page')->appends($request->except('search'));

        // Citizens tab
        $citizens = $this->usersExport->queryForTab('citizens')
            ->paginate(10, ['*'], 'citizen_page')->appends($request->except('search'));

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

        $paginator = match ($view) {
            'employees' => $users,
            'citizens' => $citizens,
            default => $all,
        };
        $everyone = $users->getCollection()->concat($citizens->getCollection())->sortByDesc('created_at')->values();
        $employeeTotal = $accountCounts['staff'] + $accountCounts['admin'];
        $employeeLatest = collect([$accountLatest['staff'] ?? null, $accountLatest['admin'] ?? null])
            ->filter()
            ->max();
        $identityVariants = [
            'verified' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
        ];
        $identityStatuses = $citizens->getCollection()->mapWithKeys(function (User $user) use ($identityVariants): array {
            $status = $user->identityStatus();

            return [
                $user->id => [
                    'status' => $status,
                    'variant' => $identityVariants[$status] ?? 'neutral',
                ],
            ];
        });
        $detailFields = [
            'status_label' => ['Account Status', null],
            'employee_id' => ['Employee ID', 'staff'],
            'employment_type_label' => ['Employment Type', 'staff'],
            'department' => ['Department', 'staff'],
            'position' => ['Position', 'staff'],
            'office' => ['Office', 'staff'],
            'phone' => ['Phone', null],
            'date_hired_label' => ['Date Hired', 'staff'],
            'gender' => ['Gender', 'citizen'],
            'birth_label' => ['Date of Birth', 'citizen'],
            'civil_status' => ['Civil Status', 'citizen'],
            'nationality' => ['Nationality', 'citizen'],
            'place_of_birth' => ['Place of Birth', 'citizen'],
            'religion' => ['Religion', 'citizen'],
            'occupation' => ['Occupation', 'citizen'],
            'address' => ['Address', 'citizen'],
            'created' => ['Account Created', null],
            'updated' => ['Last Updated', null],
        ];
        $userRecords = $everyone->mapWithKeys(function (User $user) use ($request): array {
            $employee = $user->employeeProfile;
            $citizen = $user->citizenProfile;

            return [
                $user->id => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'status_label' => self::STATUSES[$user->status] ?? ucfirst($user->status),
                    'is_self' => $request->user()->is($user),
                    'is_staff' => $user->isStaff(),
                    'permission_count' => count($user->permissions ?? []),
                    'permissions' => array_values($user->permissions ?? []),
                    'permissions_url' => route('admin.users.permissions.update', $user),
                    'avatar_url' => $user->avatar_url,
                    'phone' => $user->phone,
                    'employee_id' => $employee?->employee_id,
                    'employment_type' => $employee?->employment_type,
                    'employment_type_label' => self::EMPLOYMENT_TYPES[$employee?->employment_type] ?? null,
                    'department' => $employee?->department,
                    'position' => $employee?->position,
                    'office' => $employee?->office,
                    'date_hired' => $employee?->date_hired?->toDateString(),
                    'date_hired_label' => $employee?->date_hired?->format('M j, Y'),
                    'has_citizen' => (bool) $citizen,
                    'gender' => $citizen?->gender,
                    'birth_label' => $citizen?->date_of_birth?->format('M j, Y'),
                    'civil_status' => $citizen?->civil_status,
                    'nationality' => $citizen?->nationality,
                    'place_of_birth' => $citizen?->place_of_birth,
                    'religion' => $citizen?->religion,
                    'occupation' => $citizen?->occupation,
                    'house_number' => $citizen?->house_number,
                    'barangay' => $citizen?->barangay,
                    'city_municipality' => $citizen?->city_municipality,
                    'province' => $citizen?->province,
                    'zip_code' => $citizen?->zip_code,
                    'address' => $citizen?->full_address,
                    'created' => $user->created_at->format('M j, Y'),
                    'updated' => $user->updated_at->format('M j, Y g:i A'),
                    'update_url' => route('admin.users.update', $user),
                ],
            ];
        });

        $errors = $request->session()->get('errors');
        $createFailed = $errors !== null && $errors->getBag('default')->hasAny([
            'name',
            'email',
            'role',
            'employee_id',
            'employment_type',
            'department',
            'position',
            'phone',
            'office',
            'date_hired',
            'password',
            'avatar',
        ]);
        $updateErrors = $errors?->getBag('updateUser');
        $reopenId = $updateErrors?->any() ? (int) $request->old('edit_user_id') : null;
        $editOld = $reopenId ? $request->old() : [];
        $permissionErrors = $errors?->getBag('updatePermissions');
        $permissionsReopenId = $permissionErrors?->any()
            ? (int) $request->old('permissions_user_id')
            : null;
        $permissionList = config('permissions.list');
        $oldPermissions = (array) $request->old('permissions', []);
        $permissionsUserId = $request->old('permissions_user_id');
        $statuses = self::STATUSES;
        $statusVariants = self::STATUS_VARIANTS;
        $employmentTypes = self::EMPLOYMENT_TYPES;
        $nextEmployeeId = $this->nextEmployeeId();

        return view('admin.users.index', compact(
            'all',
            'users',
            'citizens',
            'accountCounts',
            'accountLatest',
            'createFailed',
            'reopenId',
            'editOld',
            'permissionsReopenId',
            'permissionList',
            'oldPermissions',
            'permissionsUserId',
            'view',
            'paginator',
            'everyone',
            'employeeTotal',
            'employeeLatest',
            'identityStatuses',
            'detailFields',
            'userRecords',
            'statuses',
            'statusVariants',
            'employmentTypes',
            'nextEmployeeId',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['staff', 'admin', 'citizen'])],
            'employee_id' => ['required_if:role,staff,admin', 'nullable', 'string', 'max:50', 'unique:employee_profiles,employee_id'],
            'employment_type' => ['required_if:role,staff,admin', 'nullable', Rule::in(array_keys(self::EMPLOYMENT_TYPES))],
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

        $validated = $this->otherValues($validated, $request);

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
                // New accounts start as Active (column default).
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
                        'employee_id' => $validated['employee_id'],
                        'employment_type' => $validated['employment_type'],
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
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'employment_type' => ['required_if:role,staff,admin', 'nullable', Rule::in(array_keys(self::EMPLOYMENT_TYPES))],
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

        $validated = $this->otherValues($validated, $request);

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

        if ($request->user()->is($user) && $validated['status'] !== 'active') {
            return back()
                ->withInput()
                ->withErrors(['status' => 'You cannot deactivate or suspend your own account.'], 'updateUser');
        }

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
                    'status' => $validated['status'],
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
                        'employment_type' => $validated['employment_type'],
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
            return back(fallback: route('admin.users.index'))
                ->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === 'admin' && $this->adminCount() <= 1) {
            return back(fallback: route('admin.users.index'))
                ->with('error', 'The last administrator cannot be deleted.');
        }

        // Remember the photos before the profiles are deleted.
        $photos = [$user->employeeProfile?->photo_path, $user->citizenProfile?->photo_path];

        try {
            $deleted = DB::transaction(function () use ($user): bool {
                // Issued permits must stay available for QR verification and blockchain records.
                if (ServiceRequest::query()
                    ->where('user_id', $user->id)
                    ->whereHas('issuedDocuments')
                    ->exists()
                ) {
                    return false;
                }

                $user->employeeProfile()->delete();
                $user->citizenProfile()->delete();
                $user->delete();

                return true;
            });
        } catch (QueryException $e) {
            report($e);

            return back(fallback: route('admin.users.index'))
                ->with('error', 'This account still has related records (requests, tickets, etc.), so it cannot be deleted yet.');
        }

        if (! $deleted) {
            return back(fallback: route('admin.users.index'))
                ->with('error', 'This user has issued permits and cannot be deleted. Issued permits must be preserved for QR verification and blockchain records.');
        }

        foreach ($photos as $photo) {
            $this->avatars->delete($photo);
        }

        return back(fallback: route('admin.users.index'))
            ->with('success', 'User deleted successfully.');
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

    // "Others" picked: save the typed text instead of the word "others"
    private function otherValues(array $validated, Request $request): array
    {
        foreach (['department', 'position', 'office'] as $field) {
            if (($validated[$field] ?? null) === 'others' && filled($request->input($field.'_other'))) {
                $validated[$field] = trim($request->input($field.'_other'));
            }
        }

        return $validated;
    }

    // Next free ID for this year, e.g. BSLCNPH-2026-0001
    private function nextEmployeeId(): string
    {
        $prefix = 'BSLCNPH-'.now()->year.'-';
        $last = EmployeeProfile::where('employee_id', 'like', $prefix.'%')->max('employee_id');
        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
