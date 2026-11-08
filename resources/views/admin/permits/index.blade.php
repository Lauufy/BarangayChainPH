@extends('layouts.admin')

@section('title', 'Permit & Clearance Processing')

@section('content')
    @php
        // Badge colour per status
        $statuses = [
            'pending' => 'warning',
            'processing' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'neutral',
        ];

        // Stat card label => key in $requestCounts
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

        <x-tables.card title="Permit Applications"
            description="Review submitted requests and their current status."
            :paginator="$requests->hasPages() ? $requests : null" :searchable="false" :per-page-options="[]">
            <x-tables.table
                :heads="['Reference', 'Applicant', 'Document Type', 'Date Submitted', 'Status', ['label' => 'Actions', 'align' => 'center']]"
                :empty="$requests->isEmpty()"
                message="No permit applications submitted yet."
            >
                @foreach ($requests as $request)
                    <tr>
                        <td class="whitespace-nowrap font-semibold text-slate-900">{{ $request->reference_number }}</td>
                        <td>
                            <x-tables.user-cell :name="$request->applicant_name" :sub="$request->user->email" />
                        </td>
                        <td class="text-slate-600">{{ $request->serviceTitle() }}</td>
                        <td class="whitespace-nowrap text-slate-600">
                            {{ $request->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </td>
                        <td>
                            <x-tables.badge :variant="$statuses[$request->status] ?? 'neutral'" class="capitalize">
                                {{ $request->status }}
                            </x-tables.badge>
                        </td>
                        <td class="text-center">
                            <div class="flex justify-center">
                                <x-tables.action label="Review" icon="eye" :href="route('admin.permits.show', $request)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>
@endsection