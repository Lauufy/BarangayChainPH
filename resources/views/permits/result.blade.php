@extends('layouts.permit-verification')

@section('title', 'Verification result')

@section('content')

    @if (in_array($status, ['valid', 'revoked', 'invalid', 'expired', 'unavailable']))
        <section aria-labelledby="permit-status"
            class="mb-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm sm:mb-5 sm:rounded-2xl dark:border-slate-800 dark:bg-slate-900">

            {{-- Status banner --}}
            <div
                class="{{ match ($status) {
                    'valid' => 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900',
                    'expired', 'unavailable' => 'bg-amber-50 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900',
                    default => 'bg-red-50 border-red-200 dark:bg-red-950/40 dark:border-red-900',
                } }} border-b p-3 sm:p-6">
                <div class="flex items-start gap-2.5 sm:gap-3">
                    <span
                        class="{{ match ($status) {
                            'valid' => 'bg-emerald-600',
                            'expired', 'unavailable' => 'bg-amber-500',
                            default => 'bg-red-600',
                        } }} grid size-8 shrink-0 place-items-center rounded-lg text-white sm:size-10"
                        aria-hidden="true">
                        <svg class="size-4 sm:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            @if ($status === 'valid')
                                <path d="m5 12 4 4L19 6" />
                            @else
                                <path d="M12 6v7m0 4h.01" />
                            @endif
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                            <h2 id="permit-status"
                                class="{{ match ($status) {
                                    'valid' => 'text-emerald-900 dark:text-emerald-200',
                                    'expired', 'unavailable' => 'text-amber-900 dark:text-amber-200',
                                    default => 'text-red-900 dark:text-red-200',
                                } }} text-base font-bold sm:text-xl">
                                {{ match ($status) {
                                    'valid' => 'Valid document',
                                    'revoked' => 'Permit Revoked',
                                    'invalid' => $qr ? 'Not valid' : 'File could not be authenticated',
                                    'expired' => 'Expired',
                                    'unavailable' => 'Cannot verify right now',
                                } }}
                            </h2>
                            <span
                                class="{{ match ($status) {
                                    'valid'
                                        => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900 dark:text-emerald-200 dark:border-emerald-800',
                                    'expired',
                                    'unavailable'
                                        => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900 dark:text-amber-200 dark:border-amber-800',
                                    default => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900 dark:text-red-200 dark:border-red-800',
                                } }} rounded-full border px-2 py-0.5 text-[10px] font-semibold tracking-wide sm:px-2.5 sm:text-[11px]">{{ strtoupper($status) }}</span>
                        </div>
                        @if ($status === 'revoked' && isset($document->revoked_at))
                            <p class="mt-0.5 text-xs font-medium text-red-900 sm:text-sm dark:text-red-200">
                                {{ 'Revoked on '.$document->revoked_at->format('F j, Y').'.' }}
                            </p>
                        @endif
                    </div>
                </div>

                @if ($reason || $status !== 'expired' || isset($document->expires_at))
                    <p
                        class="{{ match ($status) {
                            'valid' => 'border-emerald-200 dark:border-emerald-900',
                            'expired', 'unavailable' => 'border-amber-200 dark:border-amber-900',
                            default => 'border-red-200 dark:border-red-900',
                        } }} mt-2.5 rounded-lg border bg-white/80 px-2.5 py-2 text-xs leading-relaxed text-slate-700 sm:mt-3 sm:px-4 sm:py-2.5 sm:text-sm dark:bg-slate-900/60 dark:text-slate-200">
                        {{ $reason ??
                            match ($status) {
                                'valid' => $qr
                                    ? 'This permit matches an approved record in the barangay system.'
                                    : 'This PDF matches the current permit in the barangay and blockchain records.',
                                'revoked'
                                    => 'Do not accept this document as valid proof. This clearance was cancelled by the issuing office and is no longer active.',
                                'invalid' => 'The file could not be verified.',
                                'unavailable' => 'Blockchain confirmation is temporarily unavailable. Try again later.',
                                default => 'Expired on ' . $document->expires_at->format('F j, Y') . '.',
                            } }}
                    </p>
                @endif

                @if ($status === 'revoked' && $document?->replacement)
                    <p
                        class="{{ match ($status) {
                            'valid' => 'border-emerald-200 dark:border-emerald-900',
                            'expired', 'unavailable' => 'border-amber-200 dark:border-amber-900',
                            default => 'border-red-200 dark:border-red-900',
                        } }} mt-2.5 rounded-lg border bg-white/80 px-2.5 py-2 text-xs leading-relaxed text-slate-700 sm:mt-3 sm:px-4 sm:py-2.5 sm:text-sm dark:bg-slate-900/60 dark:text-slate-200">
                        This clearance has been replaced by a new document with reference number
                        <a class="break-all font-semibold underline"
                            href="{{ route('permits.verify.form', ['ref' => $document->replacement->document_uuid]) }}">{{ $document->replacement->document_uuid }}</a>.
                    </p>
                @endif
            </div>

            {{-- Fields that differ --}}
            @if (!empty($differences))
                <div class="border-b border-slate-200 p-3 sm:p-6 dark:border-slate-800">
                    <h3 class="text-xs font-semibold text-slate-900 sm:text-sm dark:text-white">Fields that differ</h3>
                    <ul class="mt-2 list-disc space-y-1 ps-5 text-xs text-slate-700 sm:text-sm dark:text-slate-200">
                        @foreach ($differences as $field)
                            <li class="wrap-break-word">{{ $field }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Details: two columns on mobile, long values (permit / reference no.) take the full row --}}
            @if (!empty($details))
                <div class="p-3 sm:p-6">
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-4">
                        @foreach ($details as $label => $value)
                            <div class="{{ strlen((string) $value) > 14 ? 'col-span-2 sm:col-span-1' : '' }} min-w-0">
                                <x-form.input :name="'detail_' . \Illuminate\Support\Str::slug($label, '_')" :label="$label" :value="$value" :use-old="false"
                                    :hint="$status === 'revoked' && strtolower($label) === 'expires' ? 'Voided upon revocation' : null" light readonly
                                    class="h-12! text-xs! sm:h-14! sm:text-sm! read-only:cursor-default! read-only:opacity-100!" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Lifecycle: pass $timeline from the controller, or it falls back to the document's dates --}}
            @if (!empty($timeline) || !empty($document->created_at) || !empty($document->revoked_at))
                <div class="border-t border-slate-200 p-3 sm:p-6 dark:border-slate-800">
                    <h3
                        class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 sm:text-[11px] dark:text-slate-400">
                        Status lifecycle</h3>
                    <ol class="mt-2.5 grid grid-cols-1 gap-2 min-[460px]:grid-cols-2 sm:mt-3 sm:gap-3">
                        @foreach (!empty($timeline) ? $timeline : array_filter([!empty($document->created_at) ? ['label' => 'Issued & Recorded', 'at' => $document->created_at, 'tone' => 'neutral'] : null, !empty($document->revoked_at) ? ['label' => 'Revoked by Office', 'at' => $document->revoked_at, 'tone' => 'danger'] : null]) as $event)
                            <li
                                class="flex items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 sm:gap-3 sm:rounded-xl sm:px-3 sm:py-2.5 dark:border-slate-800 dark:bg-slate-950/40">
                                <span
                                    class="{{ match ($event['tone'] ?? 'neutral') {
                                        'danger' => 'bg-red-500',
                                        'success' => 'bg-emerald-500',
                                        default => 'bg-slate-400',
                                    } }} mt-1.5 size-2 shrink-0 rounded-full sm:size-2.5"
                                    aria-hidden="true"></span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-900 sm:text-sm dark:text-white">
                                        {{ $event['label'] }}</p>
                                    @if (!empty($event['at']))
                                        <p
                                            class="mt-0.5 font-mono text-[11px] text-slate-500 sm:text-xs dark:text-slate-400">
                                            {{ $event['at'] instanceof \DateTimeInterface ? $event['at']->format('M j, Y • h:i A') : $event['at'] }}
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </section>
    @endif

    {{-- Footer action --}}
    @if ($qr)
        <aside
            class="gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:rounded-2xl sm:p-5 dark:border-slate-800 dark:bg-slate-900 print:hidden">
            <div class="flex items-start gap-2.5 sm:gap-3">
                <div class="min-w-0">
                    <h3 class="text-xs font-semibold text-slate-900 sm:text-sm dark:text-white">Registry Database Check Only
                    </h3>
                    <p class="mt-1 text-[11px] leading-relaxed text-slate-600 sm:text-xs dark:text-slate-300">
                        This page checks the permit’s current status in the <strong class="font-semibold">BarangayChain
                            PH</strong> application database. It does not represent a cryptographic on-chain file
                        verification.
                    </p>
                </div>
            </div>
            <a href="{{ route('permits.verify.form', ['ref' => $reference, 'upload' => 1]) }}"
                class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-[#0f1f3d] px-4 py-2 text-xs font-semibold text-white hover:bg-[#17325c] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 sm:min-h-11 sm:w-auto sm:shrink-0 sm:px-5 sm:py-2.5 sm:text-sm">
                Upload original PDF for blockchain check
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14m-6-6 6 6-6 6" />
                </svg>
            </a>
        </aside>
    @else
        <div class="gap-2 min-[460px]:flex-row min-[460px]:flex-wrap min-[460px]:items-center sm:gap-3 print:hidden">
            <a href="{{ route('permits.verify.form', ['ref' => $reference]) }}"
                class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-[#17325c] hover:border-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 sm:min-h-11 sm:py-2.5 sm:text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                Try another file
            </a>
            <a href="{{ route('permits.verify.form') }}"
                class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-[#17325c] hover:border-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 sm:min-h-11 sm:py-2.5 sm:text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                Verify another permit
            </a>
        </div>
    @endif
@endsection
