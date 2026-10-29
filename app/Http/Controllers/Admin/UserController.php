<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
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

        return view('admin.users', compact('users', 'accountCounts'));
    }

    /**
     * Create a staff or admin account.
     */
    public function store(Request $request): RedirectResponse
    {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['staff', 'admin'])],
            'department' => ['required_if:role,staff,admin', 'string', 'max:255'],
            'position' => ['required_if:role,staff,admin', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'office' => ['nullable', 'string', 'max:255'],
            'date_hired' => ['nullable', 'date', 'before_or_equal:today'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'max:16',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);


        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        // New staff start with the default access. An admin can change it later.
        if ($validated['role'] === 'staff') {
            $user->forceFill(['permissions' => config('permissions.staff_defaults')])->save();
        }

        $user->employeeProfile()->create([
            'employee_id' => 'BSLCNPH-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'department' => $validated['department'],
            'position' => $validated['position'],
            'phone' => $validated['phone'] ?? null,
            'office' => $validated['office'] ?? null,
            'date_hired' => $validated['date_hired'] ?? null,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'The account was created successfully.');
    }

    public function permissions(User $user): View
    {
        abort_unless($user->isStaff(), 404);

        return view('admin.user-permissions', [
            'user' => $user,
            'permissionList' => config('permissions.list'),
        ]);
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(config('permissions.list')))],
        ]);

        $user->forceFill(['permissions' => array_values($data['permissions'] ?? [])])->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'The staff permissions were updated.');
    }


    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors([
                    'account' => 'You cannot deactivate your own account.',
                ]);
        }

        if (
            $user->is_active &&
            $user->role === 'admin' &&
            User::where('role', 'admin')
            ->where('is_active', true)
            ->count() <= 1
        ) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors([
                    'account' => 'The last active administrator cannot be deactivated.',
                ]);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('admin.users.index')
            ->with('success', "The account was {$status} successfully.");
    }
}
