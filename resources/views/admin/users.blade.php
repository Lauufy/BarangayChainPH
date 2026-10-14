@extends('layouts.admin')

@section('title', 'User & Role Management')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Administration" title="User & Role Management"
            text="Manage citizen accounts, staff members, and administrator access.">
            <button type="button"
                onclick="document.getElementById('createUserModal').classList.remove('hidden'); document.getElementById('createUserModal').classList.add('flex')"
                class="rounded-lg bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">
                Add Account
            </button>
        </x-admin.page-header>

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-admin.stat-card label="Total Accounts" :value="$accountCounts['total']" note="All registered system accounts" />
            <x-admin.stat-card label="Staff Accounts" :value="$accountCounts['staff']" note="Authorized barangay personnel" />
            <x-admin.stat-card label="Administrator Accounts" :value="$accountCounts['admin']" note="Accounts with administrative access" />
        </div>

        <div class="admin-card">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">System Accounts</h2>
                <p class="mt-1 text-sm text-slate-500">Review account information, roles, and access status.</p>
            </div>

            {{-- Search and filters --}}
            <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row">
                <input type="search" placeholder="Search by name or email..." class="admin-field flex-1">
                <select class="admin-field sm:w-44">
                    <option value="">All Roles</option>
                    <option value="citizen">Citizen</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Administrator</option>
                </select>
                <select class="admin-field sm:w-44">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            {{-- Table --}}
            <x-admin.table :heads="['Account', 'Role / Employee ID', 'Status', 'Date Created', 'Actions']"
                :empty="$users->isEmpty()" message="No accounts found.">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-medium text-slate-900">{{ $user->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $user->email }}</p>
                        </td>
                        <td class="px-5 py-4 capitalize text-slate-700">
                            {{ $user->role }}
                            @if ($user->employeeProfile)
                                <p class="mt-1 text-xs font-semibold text-emerald-700">{{ $user->employeeProfile->employee_id }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $user->employeeProfile->position }} · {{ $user->employeeProfile->department }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <x-badge :variant="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Active' : 'Inactive' }}</x-badge>
                        </td>
                        <td class="px-5 py-4 text-slate-500">{{ $user->created_at->format('M j, Y') }}</td>
                        <td class="px-5 py-4">
                            @if (auth()->user()->is($user))
                                <span class="text-slate-400">Current account</span>
                            @else
                                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="font-semibold text-[#17325c] hover:underline">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>

            <div class="border-t border-slate-200 p-4">{{ $users->links() }}</div>
        </div>

        @include('admin.modals-admin.create-user')
    </div>
@endsection