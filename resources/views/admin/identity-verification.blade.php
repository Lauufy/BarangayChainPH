@extends('layouts.admin')

@section('title', 'Digital Identity Verification')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Citizen Services" title="Digital Identity Verification"
            text="Review citizen identity information and verification submissions." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Pending Reviews" :value="$counts['pending'] ?? 0" />
            <x-admin.stat-card label="Verified Citizens" :value="$counts['verified'] ?? 0" />
            <x-admin.stat-card label="Rejected Requests" :value="$counts['rejected'] ?? 0" />
        </div>

        <div class="admin-card">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Identity Verification Requests</h2>
                <p class="mt-1 text-sm text-slate-500">Review submissions before making a verification decision.</p>
            </div>

            <x-admin.table :heads="['Citizen', 'Submitted', 'Status', 'Action']"
                :empty="$verifications->isEmpty()" message="No identity verification requests yet.">
                @foreach ($verifications as $verification)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-medium text-slate-900">{{ $verification->user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $verification->user->email }}</p>
                        </td>

                        <td class="px-5 py-4 text-slate-600">{{ $verification->submitted_at->format('M j, Y g:i A') }}</td>

                        <td class="px-5 py-4">
                            <x-badge :variant="$verification->isVerified() ? 'success' : ($verification->isRejected() ? 'danger' : 'warning')">
                                {{ $verification->status }}
                            </x-badge>
                        </td>

                        <td class="px-5 py-4">
                            <a href="{{ route('admin.identity-verification.show', $verification) }}"
                                class="inline-flex rounded-md border border-[#17325c] px-3 py-1.5 text-xs font-semibold text-[#17325c] hover:bg-slate-50">
                                Review
                            </a>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>

            <div class="border-t border-slate-200 p-4">{{ $verifications->links() }}</div>
        </div>
    </div>
@endsection