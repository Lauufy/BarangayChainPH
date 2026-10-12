@extends('layouts.citizen')

@section('title', 'Identity Verification')

@section('content')
    <div class="space-y-7">
        <section>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Digital identity</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#09172D] sm:text-3xl">Identity verification</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Learn how to verify your identity for barangay services.</p>
        </section>

        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3 5 6v5c0 4.5 3 7.5 7 10 4-2.5 7-5.5 7-10V6l-7-3Z"/><path d="M12 8v4m0 4h.01"/></svg>
                </span>
                <div>
                    <h2 class="font-semibold text-[#09172D]">Online identity verification is not available yet</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">This portal does not currently accept identity documents or show a verification decision. Please contact the barangay office for in-person assistance.</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="font-semibold text-[#09172D]">For an in-person identity check</h2>
            <ol class="mt-5 grid gap-4 sm:grid-cols-3">
                <li class="rounded-xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0B2347] text-sm font-bold text-white">1</span>
                    <h3 class="mt-3 text-sm font-semibold text-[#09172D]">Contact the barangay</h3>
                    <p class="mt-1 text-sm leading-5 text-slate-600">Ask about verification hours and the accepted identification documents.</p>
                </li>
                <li class="rounded-xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0B2347] text-sm font-bold text-white">2</span>
                    <h3 class="mt-3 text-sm font-semibold text-[#09172D]">Bring your original ID</h3>
                    <p class="mt-1 text-sm leading-5 text-slate-600">Bring the documents requested by barangay staff for an in-person review.</p>
                </li>
                <li class="rounded-xl bg-slate-50 p-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#0B2347] text-sm font-bold text-white">3</span>
                    <h3 class="mt-3 text-sm font-semibold text-[#09172D]">Ask about your status</h3>
                    <p class="mt-1 text-sm leading-5 text-slate-600">The office can explain any follow-up steps after reviewing your documents.</p>
                </li>
            </ol>
        </section>
    </div>
@endsection
