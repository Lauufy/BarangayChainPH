@php

    $states = [
        'valid' => [
            'icon' => '✓',
            'label' => 'Valid',
            'message' => 'This certificate is valid.',
        ],
        'not_found' => [
            'icon' => '?',
            'label' => 'Not found',
            'message' => 'No matching certificate record was found.',
        ],
        'invalid' => [
            'icon' => '!',
            'label' => 'Invalid',
            'message' => 'This certificate could not be verified.',
        ],
        'revoked' => [
            'icon' => '×',
            'label' => 'Revoked',
            'message' => 'This certificate has been revoked.',
        ],
        'unavailable' => [
            'icon' => '…',
            'label' => 'Unavailable',
            'message' => 'Verification could not be completed right now.',
        ],
        'pending' => [
            'icon' => '…',
            'label' => 'Pending',
            'message' => 'This certificate has not been confirmed on the blockchain yet. Please check again later.',
        ],
    ];

    $state = $states[$status] ?? $states['not_found'];

    $isValid = $status === 'valid';
    $isRevoked = $status === 'revoked';

    $statusClasses = match ($status) {
        'valid' => [
            'banner' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300',
            'icon' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
        ],
        'invalid', 'revoked' => [
            'banner' => 'bg-red-50 text-red-800 dark:bg-red-950/40 dark:text-red-300',
            'icon' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        ],
        'pending', 'unavailable' => [
            'banner' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
            'icon' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
        ],
        default => [
            'banner' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            'icon' => 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
        ],
    };
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>Permit Verification | {{ $barangayName }}</title>

    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100">

    <main class="mx-auto flex min-h-screen w-full max-w-2xl flex-col px-5 py-10 sm:px-7 sm:py-14">

        {{-- Page heading --}}
        <header class="mb-7">
            <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                Public Document Verification
            </p>

            <h1 class="text-2xl font-bold tracking-tight text-[#17325c] sm:text-3xl dark:text-white">
                Permit Verification
            </h1>

            <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                Check the current status and authenticity of a document issued by {{ $barangayName }}.
            </p>
        </header>

        {{-- Verification result --}}
        <section
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            aria-label="Document verification result">

            {{-- Status banner --}}
            <div
                class="{{ $statusClasses['banner'] }} flex items-center gap-4 border-b border-black/5 px-5 py-5 sm:px-6">

                <span
                    class="{{ $statusClasses['icon'] }} flex size-11 shrink-0 items-center justify-center rounded-full text-xl font-bold"
                    aria-hidden="true">
                    {{ $state['icon'] }}
                </span>

                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight">
                        {{ $state['label'] }}
                    </h2>

                    <p class="mt-1 text-xs leading-relaxed opacity-90">
                        {{ $state['message'] }}
                    </p>
                </div>
            </div>

            {{-- Valid document details --}}
            @if ($isValid)
                <div class="p-5 sm:p-6">

                    <h3
                        class="mb-5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
                        Document Information
                    </h3>

                    <dl class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">

                        <div class="min-w-0">
                            <dt
                                class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Certificate Type
                            </dt>
                            <dd class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $documentTitle }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt
                                class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Reference Number
                            </dt>
                            <dd class="break-all text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $referenceNumber }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt
                                class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Holder
                            </dt>
                            <dd class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $maskedApplicantName }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt
                                class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Issue Date
                            </dt>
                            <dd class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $issuedAt?->format('F j, Y') }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt
                                class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Issuing Barangay
                            </dt>
                            <dd class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $barangayName }}
                            </dd>
                        </div>

                        @isset($validUntil)
                            <div class="min-w-0">
                                <dt
                                    class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                    Valid Until
                                </dt>
                                <dd class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $validUntil?->format('F j, Y') }}
                                </dd>
                            </div>
                        @endisset

                    </dl>
                </div>

                {{-- Revoked document details --}}
            @elseif ($isRevoked)
                <div class="p-5 sm:p-6">

                    <h3
                        class="mb-5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
                        Document Information
                    </h3>

                    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                        <div>
                            <dt
                                class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Certificate Type
                            </dt>
                            <dd class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $documentTitle }}
                            </dd>
                        </div>

                        <div>
                            <dt
                                class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Revoked on
                            </dt>
                            <dd class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $revokedAt?->format('F j, Y') }}
                            </dd>
                        </div>

                    </dl>
                </div>
            @endif

        </section>

        {{-- Privacy notice --}}
        @if ($isValid)
            <div class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                <span class="font-bold text-[#17325c] dark:text-blue-400" aria-hidden="true">i</span>
                <p>
                    The document holder's name is partially masked to protect their personal information.
                </p>
            </div>
        @endif

        {{-- Footer --}}
        <footer class="mt-auto pt-12">
            <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
                    {{ $barangayName }}
                </p>

                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Official public document verification page
                </p>
            </div>
        </footer>

    </main>

</body>

</html>
