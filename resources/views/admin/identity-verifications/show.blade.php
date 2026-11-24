@extends('layouts.admin')

@section('title', 'Review Identity Application')

@section('content')
    @php
        $isPending = $verification->isPending();
        $hasSelfie = (bool) $verification->selfie_path;
    @endphp

    <div class="space-y-4">

        {{-- Header --}}
        <x-admin.detail-header :title="$user->name" :subtitle="$user->email . ' · Submitted ' . $verification->submitted_at->format('M j, Y g:i A')" :crumbs="[
            ['label' => 'Identity Verification', 'href' => route('admin.identity-verification.index')],
            ['label' => 'Review Application'],
        ]">
            <x-slot:actions>
                <x-partials.stat-badge :variant="$isPending ? 'warning' : ($verification->isVerified() ? 'success' : 'danger')">
                    {{ $isPending ? 'Pending Review' : ucfirst($verification->status) }}
                </x-partials.stat-badge>

                <a href="{{ route('admin.identity-verification.index') }}"
                    class="px-15 dark:border-(--blue-900) dark:bg-(--blue-900) dark:text-(--celadon-500) dark:hover:bg-(--celadon-500) dark:hover:text-(--blue-850) inline-flex items-center justify-center rounded-md border border-slate-200 bg-white py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Back
                </a>
            </x-slot:actions>
        </x-admin.detail-header>

        <x-feedbacks.form-errors />

        {{-- TOP: applicant info (left) + evidence & decision (right) --}}
        <div class="grid items-stretch gap-4 lg:grid-cols-12">

            {{-- LEFT: Applicant Information --}}
            <div class="flex flex-col gap-4 lg:col-span-7">
                <section
                    class="shadow-xs rounded-lg border border-slate-200/80 bg-white p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4">
                        <h2 class="dark:text-(--celadon-500) text-lg font-semibold text-slate-900">Applicant Information
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Read-only details the citizen entered.
                            Verify
                            them against the document.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($fields as $label => $value)
                            <x-form.input :name="'ro_' . Str::slug($label, '_')" :label="$label" :value="$value" :light="true"
                                :raised="true" :use-old="false" readonly tabindex="-1" placeholder="Not provided"
                                class="read-only:cursor-default! read-only:opacity-100! dark:bg-slate-800! dark:text-(--white)! dark:border-slate-700! font-medium" />
                        @endforeach

                        @foreach ($wideFields as $label => $value)
                            <div class="sm:col-span-2">
                                <x-form.input :name="'ro_' . Str::slug($label, '_')" :label="$label" :value="$value" :light="true"
                                    :raised="true" :use-old="false" readonly tabindex="-1" placeholder="Not provided"
                                    class="read-only:cursor-default! read-only:opacity-100! dark:bg-slate-800! dark:text-(--white)! dark:border-slate-700! font-medium" />
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- OCR assessment (fills the space under the applicant card) --}}
                <section
                    class="shadow-xs flex-1 rounded-lg border border-slate-200/80 bg-white p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                        <h2 class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">Automated Document
                            Assessment (OCR)</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Automated text extraction results
                            and
                            potential discrepancies.</p>
                    </div>

                    @include('admin.identity-verifications.ocr-flags', [
                        'verification' => $verification,
                    ])
                </section>

                <section
                    class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                    <h2 class="font-semibold">Manual ID review required</h2>
                    <p class="mt-1">Browser checks cannot confirm the document type or side, read its text, verify the expiry date, or confirm authenticity. Review those details against the submitted ID.</p>
                </section>
            </div>

            {{-- RIGHT: Submitted Evidence + decision buttons --}}
            <section
                class="shadow-xs flex flex-col rounded-lg border border-slate-200/80 bg-white p-4 sm:p-5 lg:col-span-5 dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="dark:text-(--celadon-500) text-lg font-semibold text-slate-900">Submitted Evidence
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Compare the face on the document with the
                            selfie. Click an image to enlarge.</p>
                    </div>
                    <span
                        class="shrink-0 rounded-md bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ $verification->document_type }}
                    </span>
                </div>

                <div class="flex flex-1 flex-col gap-3">
                    {{-- Document --}}
                    <figure
                        class="group relative min-h-44 flex-1 overflow-hidden rounded-md bg-slate-900 dark:bg-slate-950 dark:ring-1 dark:ring-slate-800">
                        <figcaption
                            class="absolute left-3 top-3 z-10 rounded-md border border-white/30 bg-black/50 px-3 py-1 text-xs font-medium text-white backdrop-blur-md">
                            ID Document
                        </figcaption>

                        <div class="bg-(--gray-50) dark:bg-(--blue-870) absolute inset-0 flex items-center justify-center">
                            @if ($isImage)
                                <button type="button" data-lightbox data-src="{{ $documentUrl }}"
                                    data-title="ID Document · {{ $verification->document_type }}"
                                    class="block size-full cursor-zoom-in">
                                    <img src="{{ $documentUrl }}" alt="Uploaded identification document"
                                        class="size-full object-contain p-2 transition duration-300 group-hover:scale-[1.02]">
                                </button>
                                <a href="{{ $documentUrl }}" target="_blank" title="Open in new tab"
                                    class="absolute right-3 top-3 z-10 rounded-md border border-white/30 bg-black/50 p-1.5 text-white backdrop-blur-md transition hover:bg-black/80"
                                    aria-label="Open in new tab"><svg class="size-3.5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path
                                            d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg></a>
                            @else
                                <div class="p-6 text-center">
                                    <p class="text-sm font-semibold text-slate-300">Preview unavailable</p>
                                    <a href="{{ $documentUrl }}" target="_blank"
                                        class="mt-2 inline-block text-xs font-semibold text-sky-300 underline">Open
                                        uploaded
                                        file</a>
                                </div>
                            @endif
                        </div>
                    </figure>

                    {{-- Back of ID --}}
                    <figure
                        class="group relative min-h-44 flex-1 overflow-hidden rounded-md bg-slate-900 dark:bg-slate-950 dark:ring-1 dark:ring-slate-800">
                        <figcaption
                            class="absolute left-3 top-3 z-10 rounded-md border border-white/30 bg-black/50 px-3 py-1 text-xs font-medium text-white backdrop-blur-md">
                            Back of ID
                        </figcaption>

                        <div class="bg-(--gray-50) dark:bg-(--blue-870) absolute inset-0 flex items-center justify-center">
                            @if ($hasBackDocument && $isBackImage)
                                <button type="button" data-lightbox data-src="{{ $documentBackUrl }}"
                                    data-title="Back of ID · {{ $verification->document_type }}"
                                    class="block size-full cursor-zoom-in">
                                    <img src="{{ $documentBackUrl }}" alt="Back of uploaded identification document"
                                        class="size-full object-contain p-2 transition duration-300 group-hover:scale-[1.02]">
                                </button>
                                <a href="{{ $documentBackUrl }}" target="_blank" title="Open in new tab"
                                    class="absolute right-3 top-3 z-10 rounded-md border border-white/30 bg-black/50 p-1.5 text-white backdrop-blur-md transition hover:bg-black/80"
                                    aria-label="Open in new tab">Open</a>
                            @elseif ($hasBackDocument)
                                <div class="p-6 text-center">
                                    <p class="text-sm font-semibold text-slate-300">Preview unavailable</p>
                                    <a href="{{ $documentBackUrl }}" target="_blank"
                                        class="mt-2 inline-block text-xs font-semibold text-sky-300 underline">Open
                                        uploaded file</a>
                                </div>
                            @else
                                <div class="p-6 text-center">
                                    <p class="text-sm font-semibold text-slate-300">No back photo submitted</p>
                                </div>
                            @endif
                        </div>
                    </figure>

                    {{-- Selfie --}}
                    <figure
                        class="group relative min-h-44 flex-1 overflow-hidden rounded-md bg-slate-900 dark:bg-slate-950 dark:ring-1 dark:ring-slate-800">
                        <figcaption
                            class="absolute left-3 top-3 z-10 rounded-md border border-white/30 bg-black/50 px-3 py-1 text-xs font-medium text-white backdrop-blur-md">
                            {{ $hasSelfie ? 'Selfie · ' . $selfieCaption : 'Selfie' }}
                        </figcaption>

                        <div class="bg-(--gray-50) dark:bg-(--blue-870) absolute inset-0 flex items-center justify-center">
                            @if ($hasSelfie)
                                <button type="button" data-lightbox data-src="{{ $selfieUrl }}"
                                    data-title="Selfie · {{ $user->name }}" class="block size-full cursor-zoom-in">
                                    <img src="{{ $selfieUrl }}" alt="Citizen selfie"
                                        class="size-full object-contain p-2 transition duration-300 group-hover:scale-[1.02]">
                                </button>
                                <a href="{{ $selfieUrl }}" target="_blank" title="Open in new tab"
                                    class="absolute right-3 top-3 z-10 rounded-md border border-white/30 bg-black/50 p-1.5 text-white backdrop-blur-md transition hover:bg-black/80"
                                    aria-label="Open in new tab"><svg class="size-3.5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path
                                            d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg></a>
                            @else
                                <div class="p-6 text-center">
                                    <p class="text-sm font-semibold text-slate-300">No selfie submitted</p>
                                    <p class="mt-1 text-xs text-slate-400">Submitted before the selfie step was added.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </figure>
                </div>

                {{-- Decision buttons --}}
                @if ($isPending)
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button type="button" id="open-reject"
                            class="rounded-md bg-red-100 px-4 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-200 dark:bg-red-500/15 dark:text-red-300 dark:hover:bg-red-500/25">
                            Reject
                        </button>

                        <form id="approve-form" method="POST"
                            action="{{ route('admin.identity-verification.approve', $verification) }}">
                            @csrf
                            <button type="button" id="approve-btn"
                                class="w-full rounded-md bg-[#0a1f6b] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0c2788] disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500">
                                Approve
                            </button>
                        </form>
                    </div>
                @else
                    <div
                        class="{{ $verification->isVerified() ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-red-50 dark:bg-red-500/10' }} mt-4 rounded-md p-3">
                        <p
                            class="{{ $verification->isVerified() ? 'text-emerald-800 dark:text-emerald-300' : 'text-red-800 dark:text-red-300' }} text-sm font-semibold">
                            {{ $verification->isVerified() ? 'Application approved' : 'Application rejected' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                            by {{ $verification->reviewer?->name ?? 'a removed account' }}
                            · {{ $verification->reviewed_at?->format('M j, Y g:i A') }}
                        </p>
                        @if ($verification->isRejected())
                            <div class="mt-3 border-t border-red-100 pt-3 dark:border-red-500/20">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">
                                    Reason</p>
                                <p class="mt-1 text-sm text-red-900 dark:text-red-200">
                                    {{ $verification->rejection_reason }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </section>
        </div>

    </div>

    {{-- ============================================================ --}}
    {{-- REJECT MODAL (native <dialog>, no Alpine needed)             --}}
    {{-- ============================================================ --}}
    @if ($isPending)
        <dialog id="reject-dialog"
            class="dark:text-(--celadon-500) m-auto w-[calc(100%-2rem)] max-w-xl overflow-hidden rounded-lg bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 backdrop:backdrop-blur-sm dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
            <form id="reject-form" method="POST"
                action="{{ route('admin.identity-verification.reject', $verification) }}" class="p-4 sm:p-5" novalidate>
                @csrf

                <h3 class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">Verification decision</h3>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Review the information and supporting documents before making a decision.
                </p>

                <div class="mt-3 space-y-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                    @if ($rejectedBefore > 0)
                        <div
                            class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                            This citizen has {{ $rejectedBefore }} earlier rejected
                            {{ Str::plural('application', $rejectedBefore) }}.
                        </div>
                    @endif

                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        The citizen will see this reason and can submit a new application.
                    </p>

                    <div>
                        <label for="rejection_reason"
                            class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Reason for rejection <span class="text-red-500">*</span>
                        </label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="4" maxlength="500"
                            placeholder="Explain why this application is being rejected..."
                            class="dark:text-(--celadon-500) w-full rounded-md border border-slate-300 bg-white p-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 dark:border-slate-700 dark:bg-slate-800 dark:placeholder:text-slate-500 dark:hover:border-slate-600 dark:focus:border-blue-500 dark:focus:ring-blue-500/30">{{ old('rejection_reason') }}</textarea>
                        <p id="reason-error"
                            class="{{ $errors->has('rejection_reason') ? '' : 'hidden' }} mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $errors->first('rejection_reason') ?: 'Please enter a reason for rejection.' }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-3">
                    <button type="button" id="cancel-reject"
                        class="rounded-md bg-slate-200 px-6 py-2 text-sm font-bold text-red-600 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-red-300 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <button type="button" id="confirm-reject"
                        class="rounded-md bg-red-500 px-6 py-2 text-sm font-bold text-white transition hover:bg-red-600 disabled:opacity-60">
                        Reject
                    </button>
                </div>
            </form>
        </dialog>
    @endif

    {{-- Image lightbox --}}
    <dialog id="lightbox-dialog"
        class="m-auto w-[calc(100%-2rem)] max-w-5xl bg-transparent p-0 backdrop:bg-slate-950/90 backdrop:backdrop-blur-sm">
        <div class="mb-3 flex items-center justify-between text-white">
            <p id="lightbox-title" class="text-sm font-medium"></p>
            <div class="flex items-center gap-2">
                <a id="lightbox-open" href="#" target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-md border border-white/30 px-3 py-1 text-xs transition hover:bg-white/10">Open
                    original<svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg></a>
                <button type="button" id="lightbox-close"
                    class="inline-flex items-center gap-1.5 rounded-md border border-white/30 px-3 py-1 text-xs transition hover:bg-white/10">Close<svg
                        class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 18 18 6M6 6l12 12" />
                    </svg></button>
            </div>
        </div>
        <img id="lightbox-img" src="" alt="" class="max-h-[80vh] w-full rounded-md object-contain">
    </dialog>

    <script>
        (function() {
            // Everything is attached to `document`, not to the buttons and dialogs themselves.
            // The admin layout refreshes the page content every 5 seconds and replaces every
            // element inside it, so handlers attached to those elements would stop working.
            var backdropPress = null;

            // A press that starts on the dark backdrop (the dialog element itself) can close the dialog
            document.addEventListener('mousedown', function(e) {
                backdropPress = e.target instanceof HTMLDialogElement ? e.target : null;
            });

            document.addEventListener('click', function(e) {
                var target = e.target;

                if (backdropPress && target === backdropPress) {
                    backdropPress.close();
                }
                backdropPress = null;

                // ---- Lightbox ----
                var lightboxButton = target.closest('[data-lightbox]');
                if (lightboxButton) {
                    document.getElementById('lightbox-img').src = lightboxButton.dataset.src;
                    document.getElementById('lightbox-open').href = lightboxButton.dataset.src;
                    document.getElementById('lightbox-title').textContent = lightboxButton.dataset.title || '';
                    document.getElementById('lightbox-dialog').showModal();
                    return;
                }

                if (target.closest('#lightbox-close')) {
                    document.getElementById('lightbox-dialog').close();
                    return;
                }

                // ---- Approve ----
                var approveButton = target.closest('#approve-btn');
                if (approveButton) {
                    approveButton.disabled = true; // prevent double submit
                    document.getElementById('approve-form').submit();
                    return;
                }

                // ---- Reject modal ----
                var rejectDialog = document.getElementById('reject-dialog');
                if (!rejectDialog) return;

                if (target.closest('#open-reject')) {
                    e.preventDefault();
                    if (!rejectDialog.open) rejectDialog.showModal();
                    document.getElementById('rejection_reason').focus();
                } else if (target.closest('#cancel-reject')) {
                    rejectDialog.close();
                } else if (target.closest('#confirm-reject')) {
                    var reason = document.getElementById('rejection_reason');
                    var error = document.getElementById('reason-error');

                    if (!reason.value.trim()) {
                        error.textContent = 'Please enter a reason for rejection.';
                        error.classList.remove('hidden');
                        reason.focus();
                        return;
                    }

                    target.closest('#confirm-reject').disabled = true;
                    document.getElementById('reject-form').submit();
                }
            });

            document.addEventListener('input', function(e) {
                if (e.target.id === 'rejection_reason') {
                    document.getElementById('reason-error').classList.add('hidden');
                }
            });

            // Open the reject modal again when the server refused the reason
            @if ($errors->has('rejection_reason'))
                document.getElementById('reject-dialog')?.showModal();
            @endif
        })();
    </script>
@endsection
