@extends('layouts.admin')

@section('title', 'User & Role Management')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Administration" title="User & Role Management"
            text="Manage employee accounts, citizen accounts, and administrator access."
            :export-url="route('admin.exports.show', ['module' => 'users', 'format' => 'csv'] + request()->only('users_tab'))" />

        {{-- Summary --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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

            <x-admin.stat-card label="Employee Accounts" subtitle="Barangay personnel and staff" :value="$employeeTotal"
                :latest="$employeeLatest" latest-label="Last created" empty-text="No employee accounts yet">
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

        {{-- Accounts: one card, tabs + search + Add on one row --}}
        <x-tables.card :paginator="$paginator" :searchable="false" :per-page-options="[5, 10, 15, 25]">
            <x-tables.toolbar key="users" :tabs="['all' => 'All', 'employees' => 'Employees', 'citizens' => 'Citizens']" :search="true" search-placeholder="Start searching..."
                add-label="Add New"
                add-onclick="document.getElementById('createUserModal').classList.remove('hidden'); document.getElementById('createUserModal').classList.add('flex')" />

            {{-- ALL --}}
            @if ($view === 'all')
                <x-tables.table :heads="['Account', 'Type', 'Details', 'Status', 'Date Created', ['label' => 'Actions', 'align' => 'center']]" :empty="$everyone->isEmpty()" message="No accounts found.">
                    @foreach ($everyone as $account)
                        <tr>
                            <td>
                                <x-tables.user-cell :name="$account->name" :sub="$account->email" :avatar="$account->avatar_url" />
                            </td>

                            <td>
                                <x-tables.badge :variant="$account->role === 'citizen' ? 'neutral' : 'info'">
                                    {{ $account->role === 'citizen' ? 'Citizen' : 'Employee' }}
                                </x-tables.badge>
                            </td>

                            <td>
                                @if ($account->role === 'citizen')
                                    <p class="text-sm text-slate-900 dark:text-slate-100">{{ $account->phone ?: '—' }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $account->citizenProfile?->barangay ?: '—' }}</p>
                                @else
                                    <p class="text-sm text-slate-900 dark:text-slate-100">
                                        {{ $account->employeeProfile?->position ?? '—' }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $account->employeeProfile?->department ?? '—' }}</p>
                                @endif
                            </td>

                            <td>
                                <x-tables.badge :variant="$statusVariants[$account->status] ?? 'neutral'">
                                    {{ $statuses[$account->status] ?? ucfirst($account->status) }}
                                </x-tables.badge>
                            </td>

                            <td class="text-slate-500 dark:text-slate-400">{{ $account->created_at->format('M j, Y') }}</td>

                            <td class="text-center">@include('admin.users.row-actions', ['account' => $account])</td>
                        </tr>
                    @endforeach
                </x-tables.table>

                {{-- EMPLOYEES --}}
            @elseif ($view === 'employees')
                <x-tables.table :heads="[
                    'Employee',
                    'Employee ID',
                    'Position / Department',
                    'Status',
                    'Date Created',
                    ['label' => 'Actions', 'align' => 'center'],
                ]" :empty="$users->isEmpty()" message="No employee accounts found.">
                    @foreach ($users as $account)
                        <tr>
                            <td>
                                <x-tables.user-cell :name="$account->name" :sub="$account->email" :avatar="$account->avatar_url" />
                            </td>

                            <td>
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ $account->employeeProfile?->employee_id ?? '—' }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $account->role === 'admin' ? 'Administrator' : 'Staff' }}
                                    @if ($account->employeeProfile?->employment_type)
                                        · {{ $employmentTypes[$account->employeeProfile->employment_type] ?? '' }}
                                    @endif
                                </p>
                            </td>

                            <td>
                                <p class="text-sm text-slate-900 dark:text-slate-100">
                                    {{ $account->employeeProfile?->position ?? '—' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $account->employeeProfile?->department ?? '—' }}</p>
                            </td>

                            <td>
                                <x-tables.badge :variant="$statusVariants[$account->status] ?? 'neutral'">
                                    {{ $statuses[$account->status] ?? ucfirst($account->status) }}
                                </x-tables.badge>
                            </td>

                            <td class="text-slate-500 dark:text-slate-400">{{ $account->created_at->format('M j, Y') }}
                            </td>

                            <td class="text-center">@include('admin.users.row-actions', ['account' => $account])</td>
                        </tr>
                    @endforeach
                </x-tables.table>

                {{-- CITIZENS --}}
            @else
                <x-tables.table :heads="[
                    'Citizen',
                    'Phone',
                    'Barangay',
                    'Identity',
                    'Status',
                    'Registered',
                    ['label' => 'Actions', 'align' => 'center'],
                ]" :empty="$citizens->isEmpty()" message="No citizen accounts found.">
                    @foreach ($citizens as $account)
                        <tr>
                            <td>
                                <x-tables.user-cell :name="$account->name" :sub="$account->email" :avatar="$account->avatar_url" />
                            </td>

                            <td class="text-slate-600 dark:text-slate-300">{{ $account->phone ?: '—' }}</td>

                            <td class="text-slate-600 dark:text-slate-300">{{ $account->citizenProfile?->barangay ?: '—' }}
                            </td>

                            <td>
                                <x-tables.badge :variant="$identityStatuses[$account->id]['variant']">
                                    {{ $identityStatuses[$account->id]['status'] ?: 'none' }}
                                </x-tables.badge>
                            </td>

                            <td>
                                <x-tables.badge :variant="$statusVariants[$account->status] ?? 'neutral'">
                                    {{ $statuses[$account->status] ?? ucfirst($account->status) }}
                                </x-tables.badge>
                            </td>

                            <td class="text-slate-500 dark:text-slate-400">{{ $account->created_at->format('M j, Y') }}
                            </td>

                            <td class="text-center">@include('admin.users.row-actions', ['account' => $account])</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-tables.card>

        <script>
            window.userRecords = @json($userRecords);
            window.userInitials = (name) => (name || '').trim().split(/\s+/).slice(0, 2)
                .map((w) => w.charAt(0).toUpperCase()).join('');
        </script>

        @include('admin.users.view-user')
        @include('admin.users.edit-user')
        @include('admin.users.permissions-user')
        @include('admin.users.create-user')
    </div>

@endsection