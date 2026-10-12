@extends('layouts.admin')

@section('title', 'User & Role Management')
@section('page-title', 'User & Role Management')

@section('content')
    <div class="space-y-6">

        {{-- Page heading --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">
                    Administration
                </p>

                <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">
                    User & Role Management
                </h1>

                <p class="mt-2 text-sm text-blue-100/60">
                    Manage citizen accounts, staff members, and administrator access.
                </p>
            </div>

            <button type="button"
                onclick="document.getElementById('createUserModal').classList.remove('hidden'); document.getElementById('createUserModal').classList.add('flex')"
                class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                Add Account
            </button>

        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

            <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                <p class="text-sm text-blue-100/60">Total Accounts</p>
                <p class="mt-3 text-3xl font-bold text-white">{{ $accountCounts['total'] }}</p>
                <p class="mt-2 text-xs text-slate-500">All registered system accounts</p>
            </div>

            <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                <p class="text-sm text-blue-100/60">Staff Accounts</p>
                <p class="mt-3 text-3xl font-bold text-white">{{ $accountCounts['staff'] }}</p>
                <p class="mt-2 text-xs text-slate-500">Authorized barangay personnel</p>
            </div>

            <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                <p class="text-sm text-blue-100/60">Administrator Accounts</p>
                <p class="mt-3 text-3xl font-bold text-white">{{ $accountCounts['admin'] }}</p>
                <p class="mt-2 text-xs text-slate-500">Accounts with administrative access</p>
            </div>

        </div>

        {{-- Account table --}}
        <div class="overflow-hidden rounded-xl border border-white/10 bg-[#09172D]">

            <div class="border-b border-white/10 p-5">
                <h2 class="text-base font-semibold text-white">
                    System Accounts
                </h2>

                <p class="mt-1 text-sm text-blue-100/50">
                    Review account information, roles, and access status.
                </p>
            </div>

            {{-- Search and filters --}}
            <div class="flex flex-col gap-3 border-b border-white/10 p-4 sm:flex-row">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path stroke-linecap="round" d="m16 16 4 4" />
                    </svg>

                    <input type="search" placeholder="Search by name or email..."
                        class="w-full rounded-lg border border-white/10 bg-[#020A18] py-2.5 pl-9 pr-3 text-sm text-white outline-none placeholder:text-slate-600 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
                </div>

                <select
                    class="rounded-lg border border-white/10 bg-[#020A18] px-3 py-2.5 text-sm text-slate-300 outline-none focus:border-emerald-400">
                    <option value="">All Roles</option>
                    <option value="citizen">Citizen</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Administrator</option>
                </select>

                <select
                    class="rounded-lg border border-white/10 bg-[#020A18] px-3 py-2.5 text-sm text-slate-300 outline-none focus:border-emerald-400">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-175 w-full text-left text-sm">
                    <thead class="bg-white/3 text-xs uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-4 font-semibold">Account</th>
                            <th class="px-5 py-4 font-semibold">Role / Employee ID</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 font-semibold">Date Created</th>
                            <th class="px-5 py-4 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-white/5">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-white">{{ $user->name }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $user->email }}</p>
                                </td>
                                <td class="px-5 py-4 capitalize text-slate-300">
                                    {{ $user->role }}
                                    @if ($user->employeeProfile)
                                        <p class="mt-1 text-xs text-emerald-400">
                                            {{ $user->employeeProfile->employee_id }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $user->employeeProfile->position }} ·
                                            {{ $user->employeeProfile->department }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <x-badge :variant="$user->is_active ? 'success' : 'danger'">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </x-badge>
                                </td>
                                <td class="px-5 py-4 text-slate-400">
                                    {{ $user->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if (!auth()->user()->is($user))
                                        <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="font-semibold text-emerald-400 hover:text-emerald-300">
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-500">Current account</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    No accounts found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-white/10 p-4">
                {{ $users->links() }}
            </div>

        </div>

        @include('admin.modals-admin.create-user')

    </div>
@endsection
