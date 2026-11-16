@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Security & Accountability" title="Audit Logs"
            text="Review changes made to applications and issued documents."
            :export-url="route('admin.exports.show', ['module' => 'audit-logs'] + request()->only('user_id', 'action', 'date'))" />

        {{-- These filters narrow the activity history by actor, event, or date. --}}
        <form method="GET" action="{{ route('admin.audit-logs.index') }}"
            class="admin-card grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            <x-form.input light name="user_id" label="User ID" type="number" min="1" :value="$userFilter" />
            <x-form.input light name="action" label="Action key" :value="$actionFilter"
                placeholder="service_requests.updated" />
            <x-form.input light name="date" label="Date" type="date" :value="$dateFilter" />
            <div class="flex items-end gap-2">
                <x-form.button type="submit" color="blue" class="py-2">Filter</x-form.button>
                <a href="{{ route('admin.audit-logs.index') }}"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-600 dark:text-slate-200">Clear</a>
            </div>
        </form>

        {{-- This table shows matching audit events and provides pagination when needed. --}}
        <x-tables.card title="System Activity History"
            description="Changes to service requests and issued documents."
            :paginator="$logs" :searchable="false" item-label="audit records"
            :per-page-options="[10, 15, 20, 25, 50]">
            <x-slot:toolbar>
                <x-tables.toolbar key="audit-logs" :search="true"
                    search-placeholder="Search user, action, or record"
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="['Date & Time', 'User', 'Action', 'Record', 'Changes']"
                :empty="$isEmpty" message="No audit records match these filters.">
                @foreach ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ $log['date'] }}</td>
                        <td><x-tables.user-cell :name="$log['user']" /></td>
                        <td>{{ $log['action'] }}</td>
                        <td>{{ $log['record'] }}</td>
                        <td class="max-w-xl">{{ $log['changes'] }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>
@endsection