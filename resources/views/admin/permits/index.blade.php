@extends('layouts.admin')

@section('title', 'Permit & Clearance Processing')

@section('content')
    <div class="space-y-6">
        {{-- Explain the permit queue and summarize request totals. --}}
        <x-admin.page-header
            label="Citizen Services"
            title="Permit & Clearance Processing"
            text="Monitor applications and manage permit processing workflows."
            :export-url="route('admin.exports.show', ['module' => 'permits'])"
        />

        {{-- Key request counts use the existing stat-card component. --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach (['Total Requests' => 'total', 'Pending' => 'pending', 'Processing' => 'processing', 'Completed' => 'completed'] as $label => $key)
                <x-admin.stat-card :label="$label" :value="$requestCounts[$key]" />
            @endforeach
        </div>

        {{-- Review submitted applications and their current status. --}}
        <x-tables.card title="Permit Applications"
            description="Review submitted requests and their current status."
            :paginator="$requests" :searchable="false" item-label="requests" :per-page-options="[10, 15, 25, 50]">
            <x-slot:toolbar>
                <x-tables.toolbar key="permits" :search="true" search-placeholder="Search applicant, reference, type, or status"
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

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
                            <x-tables.badge :variant="match ($request->status) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'neutral',
                            }">
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