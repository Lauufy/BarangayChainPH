@extends('layouts.admin')

@section('title', 'Permit & Clearance Processing')

@section('content')
    <div class="admin-module space-y-5">
        {{-- Explain the permit queue and summarize request totals. --}}
        <x-admin.page-header title="Permit & Clearance Processing"
            text="Monitor applications and manage permit processing workflows." />

        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-3">
            <x-admin.stat-card label="Total Requests" subtitle="All submitted applications" :value="number_format($requestCounts['total'] ?? 0)"
                :trend="$requestTrends['total'] ?? null" :latest="$requestLatest['total'] ?? null" latest-label="Last submitted">
                <x-slot:icon>
                    {{-- Lucide: files --}}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 7h-3a2 2 0 0 1-2-2V2" />
                        <path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z" />
                        <path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending / Processing" subtitle="Awaiting review or currently handled"
                :value="number_format($requestCounts['pending'] ?? 0)" :trend="$requestTrends['pending'] ?? null" :invert="true" :latest="$requestLatest['processing'] ?? null" latest-label="Last updated">
                <x-slot:icon>
                    {{-- Lucide: clock --}}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Completed" subtitle="Approved or released" :value="number_format($requestCounts['completed'] ?? 0)" :trend="$requestTrends['completed'] ?? null"
                :latest="$requestLatest['completed'] ?? null" latest-label="Last completed">
                <x-slot:icon>
                    {{-- Lucide: circle-check --}}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- Permit directory --}}
        <x-tables.card :paginator="$requests" :searchable="false" item-label="requests" :per-page-options="[10, 15, 25, 50]">
            <x-tables.toolbar key="permits" total-label="All Requests" :total="number_format($requestCounts['total'] ?? 0)" :search="true"
                search-placeholder="Search applicant, reference, type, or status" :filters="[
                    [
                        'name' => 'status',
                        'title' => 'Request status',
                        'allLabel' => 'All statuses',
                        'options' => [
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'resubmission_required' => 'Resubmission required',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ],
                    ],
                ]" />

            <div class="account-directory">
                <x-tables.table :heads="[
                    'Applicant',
                    'Document Type',
                    'Status',
                    'Date Submitted',
                    ['label' => 'Actions', 'align' => 'center'],
                ]" :empty="$requests->isEmpty()" message="No permit applications found.">
                    @foreach ($requests as $request)
                        <x-tables.row :reference="$request->reference_number" :note="$request->purpose">
                            <td data-person>
                                <x-tables.user-cell :name="$request->applicant_name" :sub="$request->user->email" :avatar="$request->user->avatar_url" />
                            </td>
                            <td data-title data-label="Document type">{{ $request->serviceTitle() }}</td>
                            <td data-side="right">
                                <x-tables.badge class="rounded-full! gap-1.5" :variant="match ($request->status) {
                                    'pending', 'resubmission_required' => 'warning',
                                    'processing' => 'info',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'neutral',
                                }">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                </x-tables.badge>
                            </td>
                            <td data-date data-label="Date submitted" class="whitespace-nowrap">
                                {{ $request->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'N/A' }}
                            </td>
                            <td data-side="full">
                                <div class="flex justify-center">
                                    <x-tables.action label="Review" icon="file-search" :href="route('admin.permits.show', $request)" />
                                </div>
                            </td>
                        </x-tables.row>
                    @endforeach
                </x-tables.table>
            </div>
        </x-tables.card>
    </div>
@endsection
