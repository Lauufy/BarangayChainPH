@extends('layouts.citizen')

@section('title', 'My Applications')

@section('content')
    <div class="space-y-7">
        <section>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Requests</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#09172D] sm:text-3xl">My applications</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Track the progress of your barangay service requests in one place.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white px-5 py-14 text-center shadow-sm sm:px-6">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>
            </span>
            <h2 class="mt-4 font-semibold text-[#09172D]">No applications yet</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Online application submission and status tracking are not available yet. You can still review the services offered by your barangay.</p>
            <a href="{{ route('citizen.services') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0B2347] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#123365]">Browse services <span aria-hidden="true">→</span></a>
        </section>
    </div>
@endsection
