@extends($prefix === 'admin' ? 'layouts.admin' : 'layouts.staff')

@section('title', 'Review Identity Application')

@section('content')
    <div class="space-y-6">
        {{-- Heading --}}
        <div>
            <a href="{{ route($prefix . '.identity-verification.index') }}" class="text-sm font-semibold text-[#17325c] hover:underline">&larr; Back to list</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $verification->user->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Submitted {{ $verification->submitted_at->format('M j, Y g:i A') }}</p>
        </div>

        <x-feedbacks.form-errors />

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Citizen details --}}
            <div class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Citizen details</h2>
                </div>

                <dl class="grid gap-4 p-5 sm:grid-cols-2">
                    @foreach ([
                        'Name' => $verification->user->name,
                        'Email' => $verification->user->email,
                        'Phone' => $profile?->phone,
                        'Date of birth' => $profile?->date_of_birth?->format('M j, Y'),
                        'Gender' => $profile?->gender,
                        'Civil status' => $profile?->civil_status,
                        'Nationality' => $profile?->nationality,
                        'Place of birth' => $profile?->place_of_birth,
                        'Religion' => $profile?->religion,
                        'Occupation' => $profile?->occupation,
                        'Address' => $profile?->address,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 wrap-break-word text-sm text-slate-900">{{ $value ?: 'Not provided' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Uploaded ID --}}
            <div class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Uploaded ID</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $verification->document_type }}</p>
                </div>

                <div class="p-5">
                    @if ($isImage)
                        <img src="{{ route($prefix . '.identity-verification.document', $verification) }}" alt="Uploaded ID"
                            class="max-h-96 rounded-lg border border-slate-200">
                    @endif

                    <a href="{{ route($prefix . '.identity-verification.document', $verification) }}" target="_blank"
                        class="mt-4 inline-flex text-sm font-semibold text-[#17325c] hover:underline">Open file in new tab</a>
                </div>
            </div>
        </div>

        {{-- Decision --}}
        <div class="admin-card">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Decision</h2>
            </div>

            <div class="p-5">
                @if ($verification->isPending())
                    <div class="grid gap-6 lg:grid-cols-2">
                        {{-- Approve --}}
                        <form method="POST" action="{{ route($prefix . '.identity-verification.approve', $verification) }}">
                            @csrf
                            <p class="mb-3 text-sm text-slate-500">The citizen will be able to request services and edit their profile.</p>
                            <x-form.button type="submit" loadingText="Approving...">Approve</x-form.button>
                        </form>

                        {{-- Reject (reason is required) --}}
                        <form method="POST" action="{{ route($prefix . '.identity-verification.reject', $verification) }}" class="space-y-3">
                            @csrf
                            <x-form.input light type="textarea" name="rejection_reason" label="Reason for rejection" placeholder=" " required maxlength="500" />
                            <x-form.button type="submit" variant="danger" loadingText="Rejecting...">Reject</x-form.button>
                        </form>
                    </div>
                @else
                    <div class="space-y-2 text-sm text-slate-600">
                        <x-badge :variant="$verification->isVerified() ? 'success' : 'danger'">{{ $verification->status }}</x-badge>
                        <p>Reviewed by {{ $verification->reviewer?->name ?? 'a removed account' }} on {{ $verification->reviewed_at->format('M j, Y g:i A') }}</p>
                        @if ($verification->isRejected())
                            <p>Reason: {{ $verification->rejection_reason }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection