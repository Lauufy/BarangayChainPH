@extends('layouts.admin')

@section('title', 'Access Management')

@section('content')
    @php
        $statCards = [
            [
                'key' => 'total',
                'label' => 'Total Accounts',
                'subtitle' => 'All registered system accounts',
                'empty' => 'No accounts yet',
                'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" />',
            ],
            [
                'key' => 'staff',
                'label' => 'Employee Accounts',
                'subtitle' => 'Barangay personnel and staff',
                'empty' => 'No employee accounts yet',
                'icon' => '<rect x="3" y="7" width="18" height="13" rx="2" /><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18" />',
            ],
        ];

        $tableHeads = [
            'Account',
            'Role / Type',
            'Contact & Location',
            'Status',
            'Date Created',
            ['label' => 'Actions', 'align' => 'center'],
        ];

        $directoryFilters = [
            ['name' => 'status', 'title' => 'Account status', 'allLabel' => 'All statuses', 'options' => $statuses, 'selected' => $status],
        ];
    @endphp

    <div class="admin-module space-y-5">
        <x-admin.page-header title="Access Management" text="Manage employee and citizen accounts."
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
        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
            @foreach ($statCards as $card)
                <x-admin.stat-card :label="$card['label']" :subtitle="$card['subtitle']" :value="$accountCounts[$card['key']]"
                    :latest="$accountLatest[$card['key']] ?? null" latest-label="Last created" :empty-text="$card['empty']">
                    <x-slot:icon>
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            {!! $card['icon'] !!}
                        </svg>
                    </x-slot:icon>
                </x-admin.stat-card>
            @endforeach
        </div>

        {{-- Account directory --}}
        <x-tables.card :paginator="$paginator" :searchable="false" :per-page-options="[5, 10, 15, 25]">
            <x-tables.toolbar key="users" :total="$accountCounts['total']" total-label="All Accounts" :search="true"
                search-placeholder="Search by name, email, or phone" :filters="$directoryFilters" />

            <div class="account-directory">
                <x-tables.table :heads="$tableHeads" :empty="$paginator->isEmpty()" message="No accounts found.">
                    @foreach ($paginator as $account)
                        <tr>
                            <td data-title>
                                <x-tables.user-cell :name="$account->name" :sub="$account->email" :avatar="$account->avatar_url" />
                            </td>
                            <td data-label="Role">
                                <x-tables.badge variant="neutral">
                                    {{ $account->role === 'staff' ? 'Employee' : 'Citizen' }}
                                </x-tables.badge>
                            </td>
                            <td data-label="Contact & location">
                                <p>{{ $account->phone ?: 'N/A' }}</p>
                            </td>
                            <td data-side="right">
                                <x-tables.badge :variant="$statusVariants[$account->status] ?? 'neutral'" class="gap-1.5 rounded-full!">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $statuses[$account->status] ?? ucfirst($account->status) }}
                                </x-tables.badge>
                            </td>
                            <td data-label="Date created">{{ $account->created_at?->format('M j, Y') ?? 'N/A' }}</td>
                            <td data-side="full">@include('admin.users.row-actions', ['account' => $account])</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            </div>
        </x-tables.card>

        <script>
            window.userRecords = @json($userRecords);
            window.userInitials = (name) => (name || '').trim().split(/\s+/).slice(0, 2)
                .map((word) => word.charAt(0).toUpperCase()).join('');
        </script>

        @include('admin.users.view-user')
        @include('admin.users.edit-user')
        @include('admin.users.status-user')
        @include('admin.users.permissions-user')
        @include('admin.users.create-user')
    </div>
@endsection