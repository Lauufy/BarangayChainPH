@extends('layouts.admin')

@section('title', 'Access Management')

@section('content')
    <div class="admin-module space-y-5">
        <x-admin.page-header title="Access Management"
            text="Manage employee, citizen, and administrator accounts."
            :export-url="route('admin.exports.show', ['module' => 'users', 'format' => 'csv', 'users_tab' => $view, 'users_status' => $status])">
            <button type="button" aria-haspopup="dialog" aria-controls="createUserModal"
                onclick="const modal = document.getElementById('createUserModal'); modal.classList.remove('hidden'); modal.classList.add('flex')"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-(--blue-600) px-4 text-sm font-semibold text-(--white) hover:bg-(--blue-700) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-500)">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                New User
            </button>
        </x-admin.page-header>

        {{-- Summary --}}
        <div class="grid gap-3 md:grid-cols-3">
            <x-admin.stat-card label="Total Accounts" subtitle="All registered system accounts" :value="$accountCounts['total']"
                :latest="$accountLatest['total'] ?? null" latest-label="Last created" empty-text="No accounts yet">
                <x-slot:icon>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Employee Accounts" subtitle="Barangay personnel and staff" :value="$accountCounts['staff']"
                :latest="$accountLatest['staff']" latest-label="Last created" empty-text="No employee accounts yet">
                <x-slot:icon>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="7" width="18" height="13" rx="2" />
                        <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Administrators" subtitle="Accounts with administrative access" :value="$accountCounts['admin']"
                :latest="$accountLatest['admin'] ?? null" latest-label="Last created" empty-text="No administrator accounts yet">
                <x-slot:icon>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3 4 6v6c0 4.5 3.2 8 8 9 4.8-1 8-4.5 8-9V6l-8-3Z" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- Account directory --}}
        <x-tables.card :paginator="$paginator" :searchable="false" :per-page-options="[5, 10, 15, 25]">
            @include('admin.users.directory-toolbar')

            <div class="account-directory">
                <x-tables.table :heads="['Account', 'Role / Type', 'Contact & Location', 'Status', 'Date Created', ['label' => 'Actions', 'align' => 'center']]"
                    :empty="$paginator->isEmpty()" message="No accounts found.">
                    @foreach ($paginator as $account)
                        <tr>
                            <td data-title>
                                <x-tables.user-cell :name="$account->name" :sub="$account->email" :avatar="$account->avatar_url" />
                            </td>
                            <td data-label="Role">
                                <x-tables.badge :variant="$account->role === 'admin' ? 'info' : 'neutral'">
                                    {{ match ($account->role) { 'admin' => 'Admin', 'staff' => 'Employee', default => 'Citizen' } }}
                                </x-tables.badge>
                            </td>
                            <td data-label="Contact & location">
                                <p>{{ $account->phone ?: '—' }}</p>
                                <p class="text-xs text-(--gray-500) dark:text-(--gray-400)">
                                    {{ $account->citizenProfile?->barangay ?: ($account->employeeProfile?->office ?: '—') }}
                                </p>
                            </td>
                            <td data-side="right">
                                <x-tables.badge :variant="$statusVariants[$account->status] ?? 'neutral'" class="gap-1.5 rounded-full!">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $statuses[$account->status] ?? ucfirst($account->status) }}
                                </x-tables.badge>
                            </td>
                            <td data-label="Date created">{{ $account->created_at->format('M j, Y') }}</td>
                            <td data-side="full">@include('admin.users.row-actions', ['account' => $account])</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            </div>
        </x-tables.card>

        <script>
            window.userRecords = @json($userRecords);
            window.userInitials = (name) => (name || '').trim().split(/\s+/).slice(0, 2)
                .map((w) => w.charAt(0).toUpperCase()).join('');
        </script>

        @include('admin.users.view-user')
        @include('admin.users.edit-user')
        @include('admin.users.status-user')
        @include('admin.users.permissions-user')
        @include('admin.users.create-user')
    </div>

@endsection
