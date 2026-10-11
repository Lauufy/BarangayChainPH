<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $users = User::latest()->paginate(10);

        return view('admin.users.index', compact('users'));
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
            'password' => [
                'required',
                'string',
                'confirmed',
                'max:16',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'The account was created successfully.');
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
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('admin.users.index')
            ->with('success', "The account was {$status} successfully.");
    }

}