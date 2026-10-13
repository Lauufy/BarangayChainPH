@extends('layouts.admin')

@section('title', 'Notifications & Messaging')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Communication" title="Notifications & Messaging"
            text="Monitor system notifications and citizen service communications." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Total Notifications" />
            <x-admin.stat-card label="Sent Successfully" />
            <x-admin.stat-card label="Delivery Failed" />
        </div>

        <div class="admin-card">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Notification History</h2>
                <p class="mt-1 text-sm text-slate-500">Review messages and delivery status.</p>
            </div>

            {{-- Search and filter --}}
            <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row">
                <input type="search" placeholder="Search notification..." class="admin-field flex-1">
                <select class="admin-field sm:w-52">
                    <option value="">All delivery statuses</option>
                    <option value="sent">Sent</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <x-admin.table :heads="['Recipient', 'Message', 'Channel', 'Date', 'Status']"
                :empty="true" message="No notification records connected yet." />
        </div>
    </div>
@endsection