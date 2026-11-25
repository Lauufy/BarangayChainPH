@extends('layouts.admin')

@section('title', 'Permit & Clearance Processing')

@section('content')
    <div class="space-y-6">
        {{-- Explain the permit queue and summarize request totals. --}}
        <x-admin.page-header label="Citizen Services" title="Permit & Clearance Processing"
            text="Monitor applications and manage permit processing workflows." />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card label="Total Requests" subtitle="All submitted applications" :value="number_format($requestCounts['total'] ?? 0)"
                :trend="$requestTrends['total'] ?? null" :latest="$requestLatest['total'] ?? null" latest-label="Last submitted">
                <x-slot:icon>
                    {{-- Lucide: files --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 7h-3a2 2 0 0 1-2-2V2" />
                        <path d="M9 18a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l4 4v10a2 2 0 0 1-2 2Z" />
                        <path d="M3 7.6v12.8A1.6 1.6 0 0 0 4.6 22h9.8" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending / Processing" subtitle="Awaiting review or currently handled" :value="number_format($requestCounts['pending'] ?? 0)" :trend="$requestTrends['pending'] ?? null"
                :invert="true" :latest="$requestLatest['pending'] ?? null" latest-label="Oldest waiting">
                <x-slot:icon>
                    {{-- Lucide: clock --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Processing" subtitle="Currently being handled" :value="number_format($requestCounts['processing'] ?? 0)" :trend="$requestTrends['processing'] ?? null"
                :invert="true" :latest="$requestLatest['processing'] ?? null" latest-label="Last updated">
                <x-slot:icon>
                    {{-- Lucide: loader --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2v4" />
                        <path d="m16.2 7.8 2.9-2.9" />
                        <path d="M18 12h4" />
                        <path d="m16.2 16.2 2.9 2.9" />
                        <path d="M12 18v4" />
                        <path d="m4.9 19.1 2.9-2.9" />
                        <path d="M2 12h4" />
                        <path d="m4.9 4.9 2.9 2.9" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Completed" subtitle="Approved or released" :value="number_format($requestCounts['completed'] ?? 0)" :trend="$requestTrends['completed'] ?? null"
                :latest="$requestLatest['completed'] ?? null" latest-label="Last completed">
                <x-slot:icon>
                    {{-- Lucide: circle-check --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- Review submitted applications and their current status. --}}
        <x-tables.card title="Permit Applications" description="Review submitted requests and their current status."
            :paginator="$requests" :searchable="false" item-label="requests" :per-page-options="[10, 15, 25, 50]">
            <x-slot:toolbar>
                <x-tables.toolbar key="permits" :search="true"
                    search-placeholder="Search applicant, reference, type, or status"
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="[
                'Reference',
                'Applicant',
                'Document Type',
                'Date Submitted',
                'Status',
                ['label' => 'Actions', 'align' => 'center'],
            ]" :empty="$requests->isEmpty()" message="No permit applications submitted yet.">
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
                                'pending', 'resubmission_required' => 'warning',
                                'processing' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'neutral',
                            }">
                                {{ str_replace('_', ' ', $request->status) }}
                            </x-tables.badge>
                        </td>
                        <td class="text-center">
                            <div class="flex justify-center">
                                <x-tables.action label="Review" icon="file-search" :href="route('admin.permits.show', $request)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>
@endsection
