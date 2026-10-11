@extends('layouts.app')

@section('title', 'User & Role Management')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-emerald-400">ADMINISTRATION</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-white">
                User & Role Management
            </h1>
            <p class="mt-2 text-slate-400">
                View and create staff or administrator accounts.
            </p>
        </div>

        <a href="{{ route('admin.dashboard') }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-medium text-slate-300 transition hover:border-emerald-500 hover:text-white">
            Back to Dashboard
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6">
            <x- type="success">
                {{ session('success') }}
            ->
        </div>
    @endif

    <div class="mb-8 rounded-2xl border border-slate-800 bg-[#071326] p-6 sm:p-8">
        <h2 class="text-lg font-semibold text-white">Create an Account</h2>
        <p class="mt-1 text-sm text-slate-400">
            Only staff and administrator accounts can be created here.
        </p>

        <form action="{{ route('admin.users.store') }}" method="POST" class="mt-6 space-y-5">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="space-y-2">
                    <label for="name" class="block text-sm font-medium text-slate-200">
                        Full Name
                    </label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name"
                        required maxlength="255"
                        class="w-full rounded-xl border border-slate-700 bg-[#020A18] px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @error('name')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="email" class="block text-sm font-medium text-slate-200">
                        Email Address
                    </label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                        required maxlength="255"
                        class="w-full rounded-xl border border-slate-700 bg-[#020A18] px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @error('email')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="role" class="block text-sm font-medium text-slate-200">
                        Account Role
                    </label>
                    <select id="role" name="role" required
                        class="w-full rounded-xl border border-slate-700 bg-[#020A18] px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">Select a role</option>
                        <option value="staff" @selected(old('role') === 'staff')>Staff</option>
                        <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                    </select>
                    @error('role')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-slate-200">
                        Password
                    </label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        maxlength="16"
                        class="w-full rounded-xl border border-slate-700 bg-[#020A18] px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                    @error('password')
                        <p class="text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-200">
                        Confirm Password
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password" required maxlength="16"
                        class="w-full rounded-xl border border-slate-700 bg-[#020A18] px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
            </div>

            <p class="text-xs leading-5 text-slate-400">
                Passwords must be 8–16 characters and include uppercase and lowercase letters,
                a number, and a symbol. Compromised passwords are rejected.
            </p>

            <button type="submit"
                class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-[#071326]">
                Create Account
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-[#071326]">
        <div class="border-b border-slate-800 px-6 py-5">
            <h2 class="text-lg font-semibold text-white">Existing Accounts</h2>
            <p class="mt-1 text-sm text-slate-400">
                Accounts registered in the system.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#020A18] text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-4 font-medium">Name</th>
                        <th class="px-6 py-4 font-medium">Email</th>
                        <th class="px-6 py-4 font-medium">Role</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-800">
                    @forelse ($users as $user)
                        <tr class="hover:bg-white/2 transition">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-white">
                                {{ $user->name }}
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full border border-slate-700 px-2.5 py-1 text-xs font-medium text-slate-300">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($user->is_active)
                                    <span
                                        class="inline-flex rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-400">
                                        Active
                                    </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-red-500/10 px-2.5 py-1 text-xs font-medium text-red-400">
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">
                                @if (auth()->id() !== $user->id)
                                    <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                                        @csrf
                                        @method('PATCH')

                                        @if ($user->is_active)
                                            <button type="submit"
                                                class="rounded-lg border border-red-500/30 px-3 py-2 text-xs font-semibold text-red-400 transition hover:bg-red-500/10"
                                                onclick="return confirm('Deactivate this account? The user will no longer be able to access the system.')">
                                                Deactivate
                                            </button>
                                        @else
                                            <button type="submit"
                                                class="rounded-lg border border-emerald-500/30 px-3 py-2 text-xs font-semibold text-emerald-400 transition hover:bg-emerald-500/10"
                                                onclick="return confirm('Activate this account?')">
                                                Activate
                                            </button>
                                        @endif
                                    </form>
                                @else
                                    <span class="text-xs text-slate-500">Current account</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                No accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-800 px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
