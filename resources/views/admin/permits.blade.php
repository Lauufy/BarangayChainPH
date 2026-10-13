@extends('layouts.admin')

@section('title', 'Permit & Clearance Processing')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Citizen Services" title="Permit & Clearance Processing"
            text="Monitor applications and manage permit processing workflows." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card label="Total Requests" />
            <x-admin.stat-card label="Pending" />
            <x-admin.stat-card label="Processing" />
            <x-admin.stat-card label="Completed" />
        </div>

        <div class="admin-card">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Permit Applications</h2>
                <p class="mt-1 text-sm text-slate-500">Review submitted requests and their current status.</p>
            </div>

            {{-- Search and filter --}}
            <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row">
                <input type="search" placeholder="Search applicant or reference number..." class="admin-field flex-1">
                <select class="admin-field sm:w-48">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <x-admin.table :heads="['Reference', 'Applicant', 'Document Type', 'Date Submitted', 'Status']"
                :empty="true" message="No permit applications connected yet." />
        </div>
    </div>
@endsection