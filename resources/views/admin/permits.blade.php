@extends('layouts.admin')

@use('App\Models\ServiceRequest')

@section('title', 'Permit & Clearance Processing')

@section('content')
    @php
        $statuses = [
            'pending' => 'bg-amber-100 text-amber-700',
            'processing' => 'bg-blue-100 text-blue-700',
            'approved' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
            'cancelled' => 'bg-slate-100 text-slate-700',
        ];

        $counts = [
            'Total Requests' => 'total',
            'Pending' => 'pending',
            'Processing' => 'processing',
            'Completed' => 'completed',
        ];
    @endphp

    <div class="space-y-6">
        <x-admin.page-header
            label="Citizen Services"
            title="Permit & Clearance Processing"
            text="Monitor applications and manage permit processing workflows."
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($counts as $label => $key)
                <x-admin.stat-card :label="$label" :value="$requestCounts[$key]" />
            @endforeach
        </div>

        <div class="admin-card">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Permit Applications</h2>
                <p class="mt-1 text-sm text-slate-500">Review submitted requests and their current status.</p>
            </div>

            <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row">
                <input type="search" placeholder="Search applicant or reference number..." class="admin-field flex-1">
                <select class="admin-field sm:w-48">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status => $class)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <x-admin.table
                :heads="['Reference', 'Applicant', 'Document Type', 'Date Submitted', 'Status', 'Actions']"
                :empty="$requests->isEmpty()"
                message="No permit applications submitted yet."
            >
                @foreach ($requests as $request)
                    <tr>
                        <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">{{ $request->reference_number }}</td>
                        <td class="px-5 py-4">
                            <p class="font-medium text-slate-900">{{ $request->applicant_name }}</p>
                            <p class="text-xs text-slate-500">{{ $request->user->email }}</p>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            {{ match ($request->service_type) {
                                ServiceRequest::BARANGAY_CLEARANCE => 'Barangay clearance',
                                ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of residency',
                                default => 'Certificate of indigency',
                            } }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                            {{ $request->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </td>
                        <td class="px-5 py-4">
                            <span @class(['rounded-full px-2.5 py-1 text-xs font-bold capitalize', $statuses[$request->status] ?? ''])>
                                {{ $request->status }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('admin.permits.show', $request) }}"
                               class="inline-flex rounded-md border border-[#17325c] px-3 py-1.5 text-xs font-semibold text-[#17325c] hover:bg-slate-50">
                                Review
                            </a>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>

            @if ($requests->hasPages())
                <div class="border-t border-slate-200 p-4">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>
@endsection