@extends('layouts.admin')

@section('title', 'Review Identity Application')

@section('content')
    <div class="space-y-4">
        {{-- Header --}}
        <x-admin.detail-header
            :title="$user->name"
            :subtitle="$user->email . ' · Submitted ' . $verification->submitted_at->format('M j, Y g:i A')"
            :crumbs="[
                ['label' => 'Identity Verification', 'href' => route('admin.identity-verification.index')],
                ['label' => 'Review Application'],
            ]"
        >
            <x-slot:actions>
                @if ($verification->isPending())
                    <x-tables.badge variant="warning">Pending review</x-tables.badge>
                @else
                    <x-tables.badge :variant="$verification->isVerified() ? 'success' : 'danger'">
                        {{ $verification->status }}
                    </x-tables.badge>
                @endif

                <a href="{{ route('admin.identity-verification.index') }}"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back
                </a>
            </x-slot:actions>
        </x-admin.detail-header>

        {{-- Show validation feedback before the reviewer makes a decision. --}}
        <x-feedbacks.form-errors />

        <div class="flex flex-col gap-4 lg:flex-row">
            {{-- Citizen profile details and the review decision form. --}}
            <section class="admin-card min-w-0 flex-1">
                <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Citizen details</h2>

                <dl class="grid gap-x-4 gap-y-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($fields as $label => $value)
                        <div>
                            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ $label }}</dt>
                            <dd class="wrap-break-word text-sm text-slate-900">{{ $value ?: 'Not provided' }}</dd>
                        </div>
                    @endforeach

                    @foreach ($wideFields as $label => $value)
                        <div class="sm:col-span-2 xl:col-span-3">
                            <dt class="text-[11px] font-semibold uppercase text-slate-500">{{ $label }}</dt>
                            <dd class="wrap-break-word text-sm text-slate-900">{{ $value ?: 'Not provided' }}</dd>
                        </div>
                    @endforeach
                </dl>

                {{-- Decision --}}
                <div class="border-t border-slate-200 bg-slate-50/60 p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Decision</h3>

                    @if ($rejectedBefore > 0)
                        <p class="mt-1 text-xs font-semibold text-amber-700">
                            This citizen has {{ $rejectedBefore }} earlier rejected
                            {{ Str::plural('application', $rejectedBefore) }}.
                        </p>
                    @endif

                    @if ($verification->isPending())
                        <p class="mt-1 text-xs text-slate-500">
                            The citizen will be able to request services and edit their profile.
                        </p>

                        {{--
                            Two separate forms laid out as one block:
                            the reject form uses "contents" so its textarea and button
                            sit in the same grid as the approve button.
                        --}}
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            {{-- Reject (reason is required) --}}
                            <form method="POST"
                                action="{{ route('admin.identity-verification.reject', $verification) }}"
                                class="contents">
                                @csrf

                                <div class="order-1 sm:col-span-2 sm:row-start-1">
                                    <x-form.input
                                        light
                                        type="textarea"
                                        name="rejection_reason"
                                        label="Reason for rejection"
                                        placeholder=" "
                                        required
                                        maxlength="500"
                                    />
                                </div>

                                <div class="order-3 sm:col-start-2 sm:row-start-2 [&_button]:w-full">
                                    <x-form.button type="submit" variant="danger" loadingText="Rejecting...">
                                        Reject
                                    </x-form.button>
                                </div>
                            </form>

                            {{-- Approve --}}
                            <form method="POST"
                                action="{{ route('admin.identity-verification.approve', $verification) }}"
                                class="order-2 sm:col-start-1 sm:row-start-2 [&_button]:w-full">
                                @csrf

                                <x-form.button type="submit" loadingText="Approving...">
                                    Approve
                                </x-form.button>
                            </form>
                        </div>
                    @else
                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            <p>
                                Reviewed by
                                <span class="font-semibold text-slate-900">
                                    {{ $verification->reviewer?->name ?? 'a removed account' }}
                                </span>
                                on {{ $verification->reviewed_at->format('M j, Y g:i A') }}
                            </p>

                            @if ($verification->isRejected())
                                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-red-800">
                                    <p class="text-[11px] font-bold uppercase">Reason</p>
                                    <p class="mt-0.5">{{ $verification->rejection_reason }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            {{-- Uploaded ID and selfie previews, opened through permission-checked routes. --}}
            <aside class="lg:w-100 xl:w-150 space-y-4 lg:shrink-0">
                {{-- Uploaded ID --}}
                <section class="admin-card">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-slate-900">Uploaded ID</h2>
                            <p class="truncate text-xs text-slate-500">{{ $verification->document_type }}</p>
                        </div>

                        <a href="{{ $documentUrl }}" target="_blank"
                            class="shrink-0 text-xs font-semibold text-[#17325c] hover:underline">Open &nearr;</a>
                    </div>

                    <div class="flex items-center justify-center bg-slate-50 p-3">
                        @if ($isImage)
                            <a href="{{ $documentUrl }}" target="_blank">
                                <img src="{{ $documentUrl }}" alt="Uploaded ID"
                                    class="max-h-110 rounded border border-slate-200 bg-white object-contain">
                            </a>
                        @else
                            <p class="py-6 text-center text-xs text-slate-500">
                                Preview not available. Open the file to view it.
                            </p>
                        @endif
                    </div>
                </section>

                {{-- Selfie --}}
                <section class="admin-card">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-slate-900">Selfie</h2>
                            <p class="truncate text-xs text-slate-500">{{ $selfieCaption }}</p>
                        </div>

                        @if ($verification->selfie_path)
                            <a href="{{ $selfieUrl }}" target="_blank"
                                class="shrink-0 text-xs font-semibold text-[#17325c] hover:underline">Open &nearr;</a>
                        @endif
                    </div>

                    <div class="flex items-center justify-center bg-slate-50 p-3">
                        @if ($verification->selfie_path)
                            <a href="{{ $selfieUrl }}" target="_blank">
                                <img src="{{ $selfieUrl }}" alt="Selfie"
                                    class="max-h-110 rounded border border-slate-200 bg-white object-contain">
                            </a>
                        @else
                            <p class="py-6 text-center text-xs text-slate-500">
                                No selfie. This application was submitted before the selfie step.
                            </p>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection