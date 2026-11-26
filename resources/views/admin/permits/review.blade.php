@extends('layouts.admin')

@section('title', 'Review Permit Application')

@section('content')
    @if (session('registry_notice')) <p role="status" class="mb-3 text-sm">{{ session('registry_notice') }}</p> @endif
    @php
        $isApproved = $isApproved ?? false;
        $canPrintLimit = $isApproved && auth()->user()->hasPermission('permits.print-limit');
        $hasActiveDocument = $isApproved && $activeDocuments->isNotEmpty();
        $hasTools = $canPrintLimit || $hasActiveDocument;
        $expiryPresets = [3 => '3 months', 6 => '6 months', 12 => '1 year'];
        $cardClass =
            'shadow-xs rounded-lg border border-slate-200/80 bg-white p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-900';
        $titleClass = 'dark:text-(--celadon-500) text-lg font-semibold text-slate-900';
        $hintClass = 'text-xs text-slate-500 dark:text-slate-400';
        $presetClass =
            'rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700';
        $dialogClass =
            'dark:text-(--celadon-500) m-auto w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-lg bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 backdrop:backdrop-blur-sm dark:bg-slate-900 dark:ring-1 dark:ring-slate-800';
        $cancelClass =
            'rounded-md bg-slate-200 px-6 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700';
        $primaryClass =
            'rounded-md bg-[#0a1f6b] px-6 py-2 text-sm font-bold text-white transition hover:bg-[#0c2788] disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500';
        $badgeClass = match ($statusVariant) {
            'success', 'green' => 'bg-emerald-300/80 text-emerald-900 dark:bg-emerald-500/20 dark:text-emerald-300',
            'danger', 'red' => 'bg-red-200 text-red-800 dark:bg-red-500/20 dark:text-red-300',
            'warning', 'amber', 'yellow' => 'bg-amber-200 text-amber-900 dark:bg-amber-500/20 dark:text-amber-300',
            default => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        };
    @endphp

    <div class="space-y-4">

        {{-- Header --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <nav aria-label="Breadcrumb"
                    class="flex items-center gap-1 text-[11px] font-medium text-slate-700 dark:text-slate-300">
                    <a href="{{ route('admin.permits.index') }}" class="hover:underline">Permit Management</a>
                    <span aria-hidden="true">›</span>
                    <span aria-current="page">Review Application</span>
                </nav>

                <h1
                    class="mt-0.5 text-2xl font-extrabold uppercase leading-tight tracking-tight text-slate-950 dark:text-white">
                    {{ $application->serviceTitle() }}
                </h1>

                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    {{ $application->reference_number }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-xs font-semibold {{ $badgeClass }}">
                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                    {{ $statusLabel }}
                </span>

                <a href="{{ route('admin.permits.index') }}"
                    class="inline-flex items-center justify-center rounded-md bg-[#000c47] px-8 py-2 text-sm font-semibold text-white transition hover:bg-[#0a1f6b] dark:bg-blue-600 dark:hover:bg-blue-500">
                    Back
                </a>
            </div>
        </header>

        <x-feedbacks.form-errors />

        {{-- Row 1: applicant | documents. Row 2: request details | certificate tools --}}
        <div class="grid items-stretch gap-4 lg:grid-cols-12">

            {{-- Applicant --}}
            <section class="{{ $cardClass }} lg:col-span-7">
                <div class="mb-4">
                    <h2 class="{{ $titleClass }}">Applicant Information</h2>
                    <p class="{{ $hintClass }}">Read-only details the citizen entered. Check them against the
                        submitted documents.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($details as $label => $value)
                        <x-form.input :name="'ro_' . Str::slug($label, '_')" :label="$label" :value="$value" :light="true"
                            :raised="true" :use-old="false" readonly tabindex="-1" placeholder="Not provided"
                            class="read-only:cursor-default! read-only:opacity-100! dark:bg-slate-800! dark:text-(--white)! dark:border-slate-700! font-medium" />
                    @endforeach

                    <div class="sm:col-span-2">
                        <x-form.input name="ro_address" label="Address" :value="$application->address" :light="true"
                            :raised="true" :use-old="false" readonly tabindex="-1" placeholder="Not provided"
                            class="read-only:cursor-default! read-only:opacity-100! dark:bg-slate-800! dark:text-(--white)! dark:border-slate-700! font-medium" />
                    </div>
                </div>
            </section>

            {{-- Submitted documents + decision --}}
            <section @class([
                $cardClass,
                'flex flex-col lg:col-span-5',
                'lg:row-span-2' => !$hasTools,
            ])>
                <div class="mb-4">
                    <h2 class="{{ $titleClass }}">Submitted Documents</h2>
                    <p class="{{ $hintClass }}">Open each file to confirm it matches the application.</p>
                </div>

                <ul
                    class="flex-1 divide-y divide-slate-100 rounded-md border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    @forelse ($application->documents as $document)
                        <li class="flex items-center justify-between gap-3 px-3 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $document->label }}
                                </p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                    {{ $document->original_name }} · {{ number_format($document->size / 1024) }} KB
                                </p>
                            </div>

                            <a href="{{ route('admin.permits.documents.show', [$application, $document]) }}"
                                target="_blank" rel="noopener" aria-label="Open {{ $document->label }} in new tab"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                View
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path
                                        d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        </li>
                    @empty
                        <li class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            No documents were uploaded.
                        </li>
                    @endforelse
                </ul>

                {{-- Decision --}}
                @if ($isOpen)
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <button type="button" id="open-reject"
                            class="rounded-md bg-red-100 px-4 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-200 dark:bg-red-500/15 dark:text-red-300 dark:hover:bg-red-500/25">
                            Reject
                        </button>
                        <button type="button" id="open-approve"
                            class="rounded-md bg-[#0a1f6b] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0c2788] dark:bg-blue-600 dark:hover:bg-blue-500">
                            Approve
                        </button>
                    </div>
                    <button type="button" id="open-corrections" aria-haspopup="dialog" aria-controls="correction-dialog"
                        class="mt-3 w-full rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800">
                        Request Resubmission
                    </button>
                @elseif ($application->rejection_reason)
                    <div class="mt-4 rounded-md bg-red-50 p-3 dark:bg-red-500/10">
                        <p class="text-sm font-semibold text-red-800 dark:text-red-300">Application rejected</p>
                        <div class="mt-3 border-t border-red-100 pt-3 dark:border-red-500/20">
                            <p class="text-[11px] font-semibold text-red-700 dark:text-red-300">Reason</p>
                            <p class="mt-1 text-sm text-red-900 dark:text-red-200">{{ $application->rejection_reason }}</p>
                        </div>
                    </div>
                @elseif ($isApproved)
                    <div class="mt-4 rounded-md bg-emerald-50 p-3 dark:bg-emerald-500/10">
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Application approved</p>
                    </div>
                @endif
            </section>

            {{-- Request details --}}
            <section class="{{ $cardClass }} lg:col-span-7">
                <div class="mb-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h2 class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">Request Details</h2>
                    <p class="{{ $hintClass }} mt-0.5">Answers specific to this service.</p>
                </div>

                @if (count($requestDetails))
                    @php
                        // Only "Purpose" is a long answer, so it gets a growing textarea below the other fields
                        $purposeLabel = collect($requestDetails)
                            ->keys()
                            ->first(fn($label) => Str::contains(Str::lower($label), 'purpose'));
                        $inputClass =
                            'read-only:cursor-default! read-only:opacity-100! dark:bg-slate-800! dark:text-(--white)! dark:border-slate-700! font-medium';
                    @endphp

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($requestDetails as $label => $value)
                            @continue($label === $purposeLabel)
                            <x-form.input :name="'rd_' . Str::slug($label, '_')" :label="$label" :value="$value"
                                :light="true" :raised="true" :use-old="false" readonly tabindex="-1"
                                placeholder="Not provided" class="{{ $inputClass }}" />
                        @endforeach

                        @if ($purposeLabel !== null)
                            {{-- Grows with the text (field-sizing) and can be pulled taller by hand (resize-y) --}}
                            <div class="sm:col-span-2">
                                <x-form.input type="textarea" rows="1" :name="'rd_' . Str::slug($purposeLabel, '_')"
                                    :label="$purposeLabel" :value="$requestDetails[$purposeLabel]" :light="true"
                                    :raised="true" :use-old="false" readonly tabindex="-1" placeholder="Not provided"
                                    class="field-sizing-content min-h-14! resize-y! {{ $inputClass }}" />
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-slate-500 dark:text-slate-400">No additional details.</p>
                @endif
            </section>

            {{-- Certificate tools, only after approval --}}
            @if ($hasTools)
                <div class="flex flex-col gap-4 lg:col-span-5">

                    <section class="{{ $cardClass }}">
                        <h2 class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">Certificate
                            Management</h2>
                        <p class="{{ $hintClass }} mt-0.5">Manage print limits and reissue the certificate.</p>

                        <div class="mt-3 flex flex-col gap-2">
                            @if ($hasActiveDocument)
                                <button type="button" id="open-reissue" aria-haspopup="dialog"
                                    aria-controls="reissue-dialog"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-[#17325c] bg-white px-4 py-2.5 text-sm font-semibold text-[#17325c] transition hover:bg-slate-50 dark:border-slate-600 dark:bg-transparent dark:text-slate-100 dark:hover:bg-slate-800">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
                                        aria-hidden="true">
                                        <path
                                            d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Zm0 0v5h5M9 13h6M9 17h4" />
                                    </svg>
                                    Reissue Document
                                </button>
                            @endif

                            @if ($canPrintLimit)
                                <button type="button" id="open-print-limit" aria-haspopup="dialog"
                                    aria-controls="print-limit-dialog" class="{{ $primaryClass }} w-full py-2.5">
                                    Update Print Allowance
                                </button>
                            @endif
                        </div>
                    </section>

                    @if ($hasActiveDocument)
                        @php
                            $txHash = $currentRecord?->tx_hash;
                            $isRecorded = (bool) $currentRecord?->recorded_at;
                            $isConfirmed = $isRecorded && $txHash;
                            $shortHash = $txHash && strlen($txHash) > 40
                                ? substr($txHash, 0, 32) . '...' . substr($txHash, -4)
                                : $txHash;
                        @endphp

                        <section class="{{ $cardClass }} flex flex-1 flex-col">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">
                                        Blockchain Record</h2>
                                    <p class="{{ $hintClass }} mt-0.5">Cryptographic audit proof of the active
                                        certificate.</p>
                                </div>
                                <span @class([
                                    'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $isConfirmed,
                                    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => !$isConfirmed,
                                ])>
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $isConfirmed ? 'Confirmed' : ($isRecorded ? 'Needs recovery' : 'Not recorded') }}
                                </span>
                            </div>

                            <div class="mt-3 flex flex-1 flex-col gap-3">
                                @if ($isConfirmed)
                                    <div
                                        class="rounded-md border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                        <p
                                            class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Transaction hash</p>
                                        <div
                                            class="mt-1.5 flex items-center gap-2 rounded-md border border-slate-200 bg-white px-2.5 py-2 dark:border-slate-700 dark:bg-slate-800">
                                            <code title="{{ $txHash }}"
                                                class="min-w-0 flex-1 truncate text-xs text-slate-800 dark:text-slate-100">{{ $shortHash }}</code>
                                            <button type="button" data-copy="{{ $txHash }}"
                                                aria-label="Copy transaction hash"
                                                class="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                                    stroke-linejoin="round" aria-hidden="true">
                                                    <rect x="9" y="9" width="11" height="11" rx="2" />
                                                    <path d="M5 15V6a2 2 0 0 1 2-2h9" />
                                                </svg>
                                                <span data-copy-label aria-live="polite">Copy</span>
                                            </button>
                                        </div>
                                        <p class="mt-2 text-xs text-emerald-800 dark:text-emerald-300">
                                            Anchored on
                                            <span class="font-semibold">{{ $currentRecord->recorded_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}</span>
                                        </p>
                                    </div>
                                @else
                                    <div
                                        class="rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                                        @if ($isRecorded)
                                            The permit is valid on-chain, but its transaction hash still needs recovery.
                                        @else
                                            Not recorded on the blockchain yet.
                                            @if ($currentRecord?->error)
                                                <span class="mt-1 block text-xs">The last attempt failed. You can try
                                                    again.</span>
                                            @endif
                                        @endif
                                    </div>

                                    @if (auth()->user()->role === 'admin')
                                        <form method="POST"
                                            action="{{ route('admin.permits.blockchain-retry', $application) }}"
                                            class="mt-auto"
                                            onsubmit="event.preventDefault(); const form=this; tConfirmation({title:'Retry blockchain recording?',confirmLabel:'Retry'}).then(ok => { if(ok) form.submit(); });">
                                            @csrf
                                            <x-form.button type="submit" color="blue" class="w-full">
                                                {{ $isRecorded ? 'Retry transaction lookup' : 'Record on blockchain' }}
                                            </x-form.button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </section>
                    @endif
                </div>
            @endif
        </div>

        @include('admin.permits.revisions')
    </div>

    {{-- ============================================================ --}}
    {{-- CERTIFICATE MODALS (native <dialog>)                         --}}
    {{-- ============================================================ --}}
    @if ($hasActiveDocument)
        {{-- Reissue. The modal is closed before confirming, otherwise it would cover the confirmation. --}}
        <dialog id="reissue-dialog" aria-labelledby="reissue-title" class="{{ $dialogClass }}">
            <form method="POST" action="{{ route('admin.permits.reissue', $application) }}" class="p-4 sm:p-5"
                onsubmit="event.preventDefault(); var form = this; var dialog = form.closest('dialog'); dialog.close(); tConfirmation({ title: 'Reissue this document?', text: 'Are you sure you want to reissue this document? The current document will become invalid after the replacement is successfully generated.', confirmLabel: 'Yes, reissue' }).then(function (ok) { if (ok) { form.submit(); } else { dialog.showModal(); } });">
                @csrf

                <h3 id="reissue-title" class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">
                    Reissue document</h3>
                <p class="{{ $hintClass }} mt-0.5">
                    Generate a replacement authenticated PDF. The previous record is kept.
                    Active: <span class="font-semibold">Version {{ $activeDocuments->max('version') }}</span>
                </p>

                <div class="mt-3 space-y-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <x-form.input light id="reissue_expires_at" name="expires_at" type="date"
                        label="Valid until (new document)" required min="{{ now()->addDay()->toDateString() }}" />

                    @if (!$isOpen)
                        @error('expires_at')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    @endif

                    <div class="flex flex-wrap gap-2">
                        @foreach ($expiryPresets as $months => $label)
                            <button type="button" data-expiry-months="{{ $months }}"
                                class="{{ $presetClass }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-3">
                    <button type="button" data-close-dialog class="{{ $cancelClass }}">Cancel</button>
                    <button type="submit" class="{{ $primaryClass }}">Reissue</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($canPrintLimit)
        {{-- Print allowance --}}
        <dialog id="print-limit-dialog" aria-labelledby="print-limit-title" class="{{ $dialogClass }}">
            <form method="POST" action="{{ route('admin.permits.print-limit.update', $application) }}"
                class="p-4 sm:p-5">
                @csrf
                @method('PATCH')

                <h3 id="print-limit-title" class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">
                    Update print allowance</h3>
                <p class="{{ $hintClass }} mt-0.5">
                    Raise the total print allowance when helping a citizen print their certificate.
                </p>

                <div class="mt-3 space-y-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <x-form.input light id="print_limit" name="print_limit" type="number" label="Allowed prints"
                        required max="1000" min="{{ $application->print_count }}" :value="old('print_limit', $application->effectivePrintLimit())" />

                    @error('print_limit')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4 flex items-center justify-end gap-3">
                    <button type="button" data-close-dialog class="{{ $cancelClass }}">Cancel</button>
                    <button type="submit" class="{{ $primaryClass }}">Update</button>
                </div>
            </form>
        </dialog>
    @endif

    {{-- ============================================================ --}}
    {{-- DECISION MODALS (native <dialog>)                            --}}
    {{-- ============================================================ --}}
    @if ($isOpen)
        @include('admin.permits.request-corrections')

        {{-- Approve --}}
        <dialog id="approve-dialog" class="{{ $dialogClass }}">
            <form id="approve-form" method="POST" action="{{ route('admin.permits.approve', $application) }}"
                class="p-4 sm:p-5">
                @csrf

                <h3 class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">Approve application</h3>
                <p class="{{ $hintClass }} mt-0.5">
                    Choose how long the permit stays valid. The citizen can then print it.
                </p>

                <div class="mt-3 space-y-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <x-form.input light id="approve_expires_at" name="expires_at" type="date" label="Valid until"
                        required min="{{ now()->addDay()->toDateString() }}" />

                    @error('expires_at')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap gap-2">
                        @foreach ($expiryPresets as $months => $label)
                            <button type="button" data-expiry-months="{{ $months }}"
                                class="{{ $presetClass }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-3">
                    <button type="button" data-close-dialog class="{{ $cancelClass }}">Cancel</button>
                    <button type="button" id="confirm-approve" class="{{ $primaryClass }}">Approve</button>
                </div>
            </form>
        </dialog>

        {{-- Reject --}}
        <dialog id="reject-dialog"
            class="dark:text-(--celadon-500) m-auto w-[calc(100%-2rem)] max-w-xl overflow-hidden rounded-lg bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 backdrop:backdrop-blur-sm dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
            <form id="reject-form" method="POST" action="{{ route('admin.permits.reject', $application) }}"
                class="p-4 sm:p-5" novalidate>
                @csrf

                <h3 class="dark:text-(--celadon-500) text-base font-semibold text-slate-900">Reject application</h3>
                <p class="{{ $hintClass }} mt-0.5">The citizen will see this reason.</p>

                <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <label for="rejection_reason"
                        class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Reason for rejection <span class="text-red-500">*</span>
                    </label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="4" minlength="5" maxlength="500"
                        placeholder="Explain why this application is being rejected..."
                        class="dark:text-(--celadon-500) w-full rounded-md border border-slate-300 bg-white p-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 dark:border-slate-700 dark:bg-slate-800 dark:placeholder:text-slate-500 dark:hover:border-slate-600 dark:focus:border-blue-500 dark:focus:ring-blue-500/30">{{ old('rejection_reason') }}</textarea>
                    <p id="reason-error"
                        class="{{ $errors->has('rejection_reason') ? '' : 'hidden' }} mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ $errors->first('rejection_reason') ?: 'Enter a reason of at least 5 characters.' }}
                    </p>
                </div>

                <div class="mt-4 flex items-center justify-end gap-3">
                    <button type="button" data-close-dialog
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

    <script @if (app()->bound('csp-nonce')) nonce="{{ app('csp-nonce') }}" @endif>
        (function() {
            // Handlers live on `document`: the admin layout replaces page content every 5 seconds,
            // so handlers attached to individual elements would stop working.
            var backdropPress = null;

            function updateCorrections(dialog) {
                const flags = [...dialog.querySelectorAll('[name="flags[]"]')];
                flags.forEach(flag => {
                    const note = document.getElementById(flag.getAttribute('aria-controls'));
                    note.closest('[data-note]').hidden = !flag.checked;
                    note.disabled = !flag.checked;
                    note.required = flag.checked;
                });
                dialog.querySelector('[type="submit"]').disabled = !flags.some(flag => flag.checked);
            }

            function openCorrections() {
                const dialog = document.getElementById('correction-dialog');
                updateCorrections(dialog);
                dialog.showModal();
            }

            function openDialog(id) {
                var dialog = document.getElementById(id);
                if (dialog && !dialog.open) dialog.showModal();
            }

            // The Clipboard API needs HTTPS or localhost, so keep a fallback for plain HTTP
            function copyText(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text);
                }

                var area = document.createElement('textarea');
                area.value = text;
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                var copied = document.execCommand('copy');
                area.remove();

                return copied ? Promise.resolve() : Promise.reject();
            }

            document.addEventListener('change', function(e) {
                if (e.target.matches('#correction-dialog input[type="checkbox"]')) {
                    updateCorrections(e.target.closest('dialog'));
                }
            });

            function setExpiry(button, months) {
                var date = new Date();
                var day = date.getDate();

                date.setDate(1);
                date.setMonth(date.getMonth() + months);
                date.setDate(Math.min(day, new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate()));

                button.form.elements.namedItem('expires_at').value = [
                    date.getFullYear(),
                    String(date.getMonth() + 1).padStart(2, '0'),
                    String(date.getDate()).padStart(2, '0'),
                ].join('-');
            }

            document.addEventListener('mousedown', function(e) {
                backdropPress = e.target instanceof HTMLDialogElement ? e.target : null;
            });

            document.addEventListener('click', function(e) {
                var target = e.target;

                // Press that starts and ends on the dark backdrop closes the dialog
                if (backdropPress && target === backdropPress) {
                    backdropPress.close();
                }
                backdropPress = null;

                // Expiry presets (approve and reissue dialogs)
                var preset = target.closest('[data-expiry-months]');
                if (preset) {
                    setExpiry(preset, parseInt(preset.dataset.expiryMonths, 10));
                    return;
                }

                var closer = target.closest('[data-close-dialog]');
                if (closer) {
                    closer.closest('dialog').close();
                    return;
                }

                var copier = target.closest('[data-copy]');
                if (copier) {
                    var label = copier.querySelector('[data-copy-label]');
                    copyText(copier.dataset.copy).then(function() {
                        label.textContent = 'Copied';
                    }, function() {
                        label.textContent = 'Copy failed';
                    }).then(function() {
                        setTimeout(function() { label.textContent = 'Copy'; }, 2000);
                    });
                    return;
                }

                var approveDialog = document.getElementById('approve-dialog');
                var rejectDialog = document.getElementById('reject-dialog');

                if (target.closest('#open-reissue')) {
                    openDialog('reissue-dialog');
                } else if (target.closest('#open-print-limit')) {
                    openDialog('print-limit-dialog');
                } else if (target.closest('#open-corrections')) {
                    openCorrections();
                } else if (target.closest('#open-approve') && approveDialog) {
                    openDialog('approve-dialog');
                } else if (target.closest('#open-reject') && rejectDialog) {
                    openDialog('reject-dialog');
                    document.getElementById('rejection_reason').focus();
                } else if (target.closest('#confirm-approve')) {
                    var form = document.getElementById('approve-form');
                    if (!form.reportValidity()) return;
                    target.closest('#confirm-approve').disabled = true; // prevent double submit
                    form.submit();
                } else if (target.closest('#confirm-reject')) {
                    var reason = document.getElementById('rejection_reason');
                    var error = document.getElementById('reason-error');

                    if (reason.value.trim().length < 5) {
                        error.textContent = 'Enter a reason of at least 5 characters.';
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

            // Reopen the right dialog when the server refused the input
            @if ($isOpen && $errors->hasAny(['flags', 'flags.*', 'notes.*', 'message']))
                openCorrections();
            @elseif ($isOpen && $errors->has('rejection_reason'))
                openDialog('reject-dialog');
            @elseif ($isOpen && $errors->has('expires_at'))
                openDialog('approve-dialog');
            @elseif ($errors->has('expires_at'))
                openDialog('reissue-dialog');
            @elseif ($errors->has('print_limit'))
                openDialog('print-limit-dialog');
            @endif
        })();
    </script>
@endsection