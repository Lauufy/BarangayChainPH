<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Permit Verification | BarangayChain PH</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-slate-50 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-4xl items-center gap-3 px-5 py-4 sm:px-8">
            <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="BarangayChain PH logo" class="h-11 w-11 object-contain">
            <div>
                <p class="text-base font-bold tracking-tight text-[#17325c]">BarangayChain PH</p>
                <p class="text-xs text-slate-500">Barangay San Luis, Antipolo City</p>
            </div>
        </div>
    </header>

    <main class="mx-auto flex max-w-4xl flex-col px-5 py-10 sm:px-8 sm:py-16">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Public document verification</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#17325c] sm:text-3xl">Permit Verification</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Check the current status of a document issued by Barangay San Luis.
            </p>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @if ($isGenuine)
                <div class="flex items-center gap-4 border-b border-emerald-100 bg-emerald-50 px-5 py-5 sm:px-7">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-emerald-800">Valid document</p>
                        <p class="mt-1 text-sm text-emerald-700">This permit matches an approved record in the barangay system.</p>
                    </div>
                </div>

                <div class="space-y-5 px-5 py-6 sm:px-7">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Document type</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $documentTitle }}</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Permit number</p>
                            <p class="mt-1 break-all font-semibold text-slate-900">{{ $permitNumber }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reference number</p>
                            <p class="mt-1 break-all font-semibold text-slate-900">{{ $referenceNumber }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Applicant</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $maskedApplicantName }}</p>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-4 border-b border-red-100 bg-red-50 px-5 py-5 sm:px-7">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-red-800">Invalid or unapproved document</p>
                        <p class="mt-1 text-sm text-red-700">This record does not currently meet the requirements for a valid permit.</p>
                    </div>
                </div>
                <div class="px-5 py-6 sm:px-7">
                    <p class="text-sm leading-6 text-slate-600">
                        The document may be unapproved, incomplete, or no longer match an approved permit record.
                        Please contact Barangay San Luis for assistance.
                    </p>
                </div>
            @endif
        </section>

        <p class="mt-6 text-xs leading-5 text-slate-500">
            This page checks the permit's current status in the BarangayChain PH application database.
            It does not represent an on-chain blockchain verification.
        </p>

        <footer class="mt-10 border-t border-slate-200 pt-5 text-xs text-slate-500">
            <p class="font-semibold text-slate-700">Barangay San Luis, Antipolo City, Rizal</p>
            <p class="mt-1">Official public document verification page</p>
        </footer>
    </main>
</body>
</html>
