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

        <x-tables.card title="Identity Verification Requests"
            description="Review submissions before making a verification decision."
            :paginator="$verifications->hasPages() ? $verifications : null"
            :searchable="false" :per-page-options="[]">
            <x-tables.table :heads="['Citizen', 'Submitted', 'Status', ['label' => 'Action', 'align' => 'center']]"
                :empty="$verifications->isEmpty()" message="No identity verification requests yet.">
                @foreach ($verifications as $verification)
                    <tr>
                        <td>
                            <x-tables.user-cell :name="$verification->user->name"
                                :sub="$verification->user->email" :avatar="$verification->user->avatar_url" />
                        </td>

                        <td class="text-slate-600">{{ $verification->submitted_at->format('M j, Y g:i A') }}</td>

                        <td>
                            <x-tables.badge :variant="$verification->isVerified() ? 'success' : ($verification->isRejected() ? 'danger' : 'warning')">
                                {{ $verification->status }}
                            </x-tables.badge>
                        </td>

                        <td class="text-center">
                            <div class="flex justify-center">
                                <x-tables.action label="Review" icon="eye"
                                    :href="route('admin.identity-verification.show', $verification)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>
@endsection