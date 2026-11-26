@extends('layouts.admin')

@section('title', 'Digital Identity Verification')

@section('content')
    <div class="admin-module space-y-5">
        {{-- Explain the review queue and summarize current identity-verification activity. --}}
        <x-admin.page-header title="Digital Identity Verification"
            text="Review citizen identity information and verification submissions." />

        {{-- Pending, verified, and rejected submission totals. --}}
        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-3">
            <x-admin.stat-card label="Pending Reviews" subtitle="Awaiting a decision" :value="$counts['pending'] ?? 0" :latest="$latest['pending'] ?? null"
                latest-label="Last submitted" empty-text="Nothing waiting for review" :invert="true">
                <x-slot:icon>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Verified Citizens" subtitle="Approved identities" :value="$counts['verified'] ?? 0" :latest="$latest['verified'] ?? null"
                latest-label="Last verified" empty-text="No verified citizens yet">
                <x-slot:icon>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3 4 6v6c0 4.5 3.2 8 8 9 4.8-1 8-4.5 8-9V6l-8-3Z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Rejected Requests" subtitle="Declined submissions" :value="$counts['rejected'] ?? 0" :latest="$latest['rejected'] ?? null"
                latest-label="Last rejected" empty-text="No rejected requests" :invert="true">
                <x-slot:icon>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="m9 9 6 6M15 9l-6 6" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- Verification directory --}}
        <x-tables.card :paginator="$verifications" :searchable="false" item-label="requests"
            :per-page-options="[10, 15, 25, 50]">
            <x-tables.toolbar key="identity-verifications" total-label="All Requests"
                :total="($counts['pending'] ?? 0) + ($counts['verified'] ?? 0) + ($counts['rejected'] ?? 0)"
                :search="true" search-placeholder="Search by name or email" :filters="[[
                    'name' => 'status',
                    'title' => 'Verification status',
                    'allLabel' => 'All statuses',
                    'options' => ['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'],
                ]]" />

            <div class="account-directory">
                <x-tables.table :heads="['Citizen', 'Submitted', 'Status', ['label' => 'Actions', 'align' => 'center']]"
                    :empty="$verifications->isEmpty()" message="No identity verification requests found.">
                    @foreach ($verifications as $verification)
                        <tr>
                            <td data-title>
                                <x-tables.user-cell :name="$verification->user->name" :sub="$verification->user->email"
                                    :avatar="$verification->user->avatar_url" />
                            </td>
                            <td data-label="Submitted">
                                {{ $verification->submitted_at?->format('M j, Y g:i A') ?? 'N/A' }}
                            </td>
                            <td data-side="right">
                                <x-tables.badge class="gap-1.5 rounded-full!" :variant="match (true) {
                                    $verification->isVerified() => 'success',
                                    $verification->isRejected() => 'danger',
                                    default => 'warning',
                                }">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ ucfirst($verification->status) }}
                                </x-tables.badge>
                            </td>
                            <td data-side="full">
                                <div class="flex justify-center">
                                    <x-tables.action label="Review" icon="file-search"
                                        :href="route('admin.identity-verification.show', $verification)" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            </div>
        </x-tables.card>
    </div>
@endsection