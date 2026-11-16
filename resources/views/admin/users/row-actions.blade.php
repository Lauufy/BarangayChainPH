{{-- View / Edit / Delete buttons for one account row. Expects $account. --}}
<div class="flex justify-center gap-1">
    <x-tables.action label="View" icon="eye" icon-only onclick="openViewUserModal({{ $account->id }})" />
    <x-tables.action label="Edit" icon="pencil" icon-only onclick="openEditUserModal({{ $account->id }})" />

    @if (auth()->user()->is($account))
        <span class="self-center pl-1 text-xs text-slate-400">You</span>
    @else
        <x-tables.action label="Delete" icon="trash" variant="danger" icon-only
            :action="route('admin.users.destroy', $account)"
            method="DELETE"
            confirm="Delete {{ $account->name }}? This cannot be undone." />
    @endif
</div>