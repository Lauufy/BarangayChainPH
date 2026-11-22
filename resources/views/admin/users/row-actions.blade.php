{{-- View / Edit / Status buttons for one account row. Expects $account. --}}
<div class="flex justify-center gap-1">
    <x-tables.action label="View" icon="file-search" icon-only onclick="openViewUserModal({{ $account->id }})" />
    <x-tables.action label="Edit" icon="pencil" icon-only onclick="openEditUserModal({{ $account->id }})" />

    @if (auth()->user()->is($account))
        <span class="self-center pl-1 text-xs text-slate-400">You</span>
    @else
        <x-tables.action label="Set status" icon="power" icon-only
            onclick="openUserStatusModal({{ $account->id }})" />
    @endif
</div>