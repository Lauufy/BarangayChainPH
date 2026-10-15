@extends($prefix === 'admin' ? 'layouts.admin' : 'layouts.staff')

@section('title', 'Review Identity Application')

@section('content')
    <div class="space-y-4">
        {{-- Header --}}
        <header class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route($prefix . '.identity-verification.index') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50"
                    aria-label="Back to list" title="Back to list">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </a>

                <div class="min-w-0">
                    <h1 class="truncate text-xl font-bold leading-tight text-slate-900">{{ $verification->user->name }}</h1>
                    <p class="truncate text-xs text-slate-500">
                        {{ $verification->user->email }} &middot; Submitted {{ $verification->submitted_at->format('M j, Y g:i A') }}
                    </p>
                </div>
            </div>

            @if ($verification->isPending())
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Pending review
                </span>
            @else
                <x-badge :variant="$verification->isVerified() ? 'success' : 'danger'">{{ $verification->status }}</x-badge>
            @endif
        </header>

        <x-feedbacks.form-errors />

        <div class="flex flex-col gap-4 lg:flex-row">
            {{-- Left: citizen details + decision --}}
            <section class="admin-card min-w-0 flex-1">
                <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-900">Citizen details</h2>

                <dl class="grid gap-x-4 gap-y-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Name</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $verification->user->name ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Date of birth</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->date_of_birth?->format('M j, Y') ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Gender</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->gender ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Civil status</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->civil_status ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Nationality</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->nationality ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Place of birth</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->place_of_birth ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Religion</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->religion ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Occupation</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->occupation ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Phone</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->phone ?: 'Not provided' }}</dd>
                    </div>
                    <div class="sm:col-span-2 xl:col-span-3">
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Email</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $verification->user->email ?: 'Not provided' }}</dd>
                    </div>
                    <div class="sm:col-span-2 xl:col-span-3">
                        <dt class="text-[11px] font-semibold uppercase text-slate-500">Address</dt>
                        <dd class="wrap-break-word text-sm text-slate-900">{{ $profile?->address ?: 'Not provided' }}</dd>
                    </div>
                </dl>

                {{-- Decision --}}
                <div class="border-t border-slate-200 bg-slate-50/60 p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Decision</h3>

                    @if ($verification->isPending())
                        <p class="mt-1 text-xs text-slate-500">The citizen will be able to request services and edit their profile.</p>

                        {{--
                            Two separate forms laid out as one block:
                            the reject form uses "contents" so its textarea and button
                            sit in the same grid as the approve button.
                        --}}
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            {{-- Reject (reason is required) --}}
                            <form method="POST" action="{{ route($prefix . '.identity-verification.reject', $verification) }}"
                                class="contents">
                                @csrf

                                <div class="order-1 sm:col-span-2 sm:row-start-1">
                                    <x-form.input light type="textarea" name="rejection_reason" label="Reason for rejection" placeholder=" " required maxlength="500" />
                                </div>

                                <div class="order-3 sm:col-start-2 sm:row-start-2 [&_button]:w-full">
                                    <x-form.button type="submit" variant="danger" loadingText="Rejecting...">Reject</x-form.button>
                                </div>
                            </form>

                            {{-- Approve --}}
                            <form method="POST" action="{{ route($prefix . '.identity-verification.approve', $verification) }}"
                                class="order-2 sm:col-start-1 sm:row-start-2 [&_button]:w-full">
                                @csrf
                                <x-form.button type="submit" loadingText="Approving...">Approve</x-form.button>
                            </form>
                        </div>
                    @else
                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            <p>
                                Reviewed by
                                <span class="font-semibold text-slate-900">{{ $verification->reviewer?->name ?? 'a removed account' }}</span>
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

            {{-- Right: uploaded ID --}}
            <aside class="lg:w-100 lg:shrink-0 xl:w-150">
                <section class="admin-card flex h-full flex-col">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-slate-900">Uploaded ID</h2>
                            <p class="truncate text-xs text-slate-500">{{ $verification->document_type }}</p>
                        </div>
                        <a href="{{ route($prefix . '.identity-verification.document', $verification) }}" target="_blank"
                            class="shrink-0 text-xs font-semibold text-[#17325c] hover:underline">Open &nearr;</a>
                    </div>

                    <div class="flex flex-1 items-center justify-center bg-slate-50 p-3">
                        @if ($isImage)
                            <a href="{{ route($prefix . '.identity-verification.document', $verification) }}" target="_blank">
                                <img src="{{ route($prefix . '.identity-verification.document', $verification) }}" alt="Uploaded ID"
                                    class="max-h-110 rounded border border-slate-200 bg-white object-contain">
                            </a>
                        @else
                            <p class="py-6 text-center text-xs text-slate-500">Preview not available. Open the file to view it.</p>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection