@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Security & Accountability" title="Audit Logs"
            text="Review changes made to applications and issued documents." />

        <form method="GET" action="{{ route('admin.audit-logs.index') }}"
            class="admin-card grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm font-medium text-slate-700">
                User ID
                <input type="number" min="1" name="user_id" value="{{ $userFilter }}" class="admin-field mt-1">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Action key
                <input type="text" name="action" value="{{ $actionFilter }}" placeholder="service_requests.updated"
                    class="admin-field mt-1">
            </label>
            <label class="text-sm font-medium text-slate-700">
                Date
                <input type="date" name="date" value="{{ $dateFilter }}" class="admin-field mt-1">
            </label>
            <div class="flex items-end gap-2">
                <button class="rounded-md bg-[#17325c] px-4 py-2 text-sm font-semibold text-white">Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Clear</a>
            </div>
        </form>

        <x-tables.card title="System Activity History"
            description="Changes to service requests and issued documents."
            :paginator="$logs->hasPages() ? $logs : null"
            :searchable="false" :per-page-options="[]">
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
