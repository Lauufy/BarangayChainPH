@extends('layouts.admin')

@section('title', 'User & Role Management')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Administration" title="User & Role Management"
            text="Manage citizen accounts, staff members, and administrator access." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-admin.stat-card label="Total Accounts" :value="$accountCounts['total']" note="All registered system accounts" />
            <x-admin.stat-card label="Staff Accounts" :value="$accountCounts['staff']" note="Authorized barangay personnel" />
            <x-admin.stat-card label="Administrator Accounts" :value="$accountCounts['admin']"
                note="Accounts with administrative access" />
        </div>

        <x-tables.card title="System Accounts" description="Review account information, roles, and access status."
            :paginator="$users" item-label="accounts" search-placeholder="Search by name or email"
            add-label="Add New"
            add-onclick="document.getElementById('createUserModal').classList.remove('hidden'); document.getElementById('createUserModal').classList.add('flex')"
            :filters="[
                [
                    'name' => 'role',
                    'label' => 'Role',
                    'placeholder' => 'All roles',
                    'options' => ['citizen' => 'Citizen', 'staff' => 'Staff', 'admin' => 'Administrator'],
                ],
            ]">

            <x-tables.table :heads="['Account', 'Role / Employee ID', 'Date Created', ['label' => 'Actions', 'align' => 'center']]"
                :empty="$users->isEmpty()" message="No accounts found.">
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <x-tables.user-cell :name="$user->name" :sub="$user->email" :avatar="$user->avatar_url" />
                        </td>

                        <td>
                            <p class="font-medium capitalize text-slate-900">{{ $user->role }}</p>
                            @if ($user->employeeProfile)
                                <p class="text-xs font-semibold text-emerald-700">{{ $user->employeeProfile->employee_id }}</p>
                                <p class="text-xs text-slate-500">{{ $user->employeeProfile->position }} ·
                                    {{ $user->employeeProfile->department }}</p>
                            @endif
                        </td>

                        <td class="text-slate-500">{{ $user->created_at->format('M j, Y') }}</td>

                        <td class="text-center">
                            <div class="flex justify-center gap-1">
                                <x-tables.action label="View" icon="eye" icon-only
                                    onclick="openViewUserModal({{ $user->id }})" />
                                <x-tables.action label="Edit" icon="pencil" icon-only
                                    onclick="openEditUserModal({{ $user->id }})" />

                                @if (auth()->user()->is($user))
                                    <span class="self-center pl-1 text-xs text-slate-400">You</span>
                                @else
                                    <x-tables.action label="Delete" icon="trash" variant="danger"
                                        icon-only
                                        :action="route('admin.users.destroy', $user)" method="DELETE"
                                        confirm="Delete {{ $user->name }}? This cannot be undone." />
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>

        {{-- Data the View / Edit popups read from (no extra requests needed) --}}
        @php
            $userRecords = $users->getCollection()->mapWithKeys(function ($u) {
                $p = $u->employeeProfile; // staff / admin details
                $c = $u->citizenProfile; // citizen details

                return [
                    $u->id => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => $u->role,
                        'is_self' => auth()->user()->is($u),
                        'is_staff' => $u->isStaff(),
                        'permission_count' => count($u->permissions ?? []),
                        'permissions' => array_values($u->permissions ?? []),
                        'permissions_url' => route('admin.users.permissions.update', $u),
                        'avatar_url' => $u->avatar_url,
                        'phone' => $u->phone,
                        // staff / admin
                        'employee_id' => $p?->employee_id,
                        'department' => $p?->department,
                        'position' => $p?->position,
                        'office' => $p?->office,
                        'date_hired' => $p?->date_hired?->toDateString(),
                        'date_hired_label' => $p?->date_hired?->format('M j, Y'),
                        // citizen
                        'has_citizen' => (bool) $c,
                        'gender' => $c?->gender,
                        'birth_label' => $c?->date_of_birth?->format('M j, Y'),
                        'civil_status' => $c?->civil_status,
                        'nationality' => $c?->nationality,
                        'place_of_birth' => $c?->place_of_birth,
                        'religion' => $c?->religion,
                        'occupation' => $c?->occupation,
                        'house_number' => $c?->house_number,
                        'barangay' => $c?->barangay,
                        'city_municipality' => $c?->city_municipality,
                        'province' => $c?->province,
                        'zip_code' => $c?->zip_code,
                        'address' => $c?->full_address,
                        'created' => $u->created_at->format('M j, Y'),
                        'updated' => $u->updated_at->format('M j, Y g:i A'),
                        'update_url' => route('admin.users.update', $u),
                    ],
                ];
            });
        @endphp
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