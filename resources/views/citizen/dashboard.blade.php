@extends('layouts.citizen')

@section('title', 'Citizen Dashboard')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-medium text-emerald-400">CITIZEN PORTAL</p>

        <h1 class="mt-2 text-3xl font-bold tracking-tight text-white">
            Welcome, {{ auth()->user()->name }}!
        </h1>

        <p class="mt-2 text-slate-400">
            Access your barangay's digital services in one place.
        </p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-[#071326] p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z" />
                </svg>
            </div>

            <h2 class="font-semibold text-white">Permit Requests</h2>

            <p class="mt-2 text-sm leading-6 text-slate-400">
                Request barangay permits and clearances online.
            </p>

            <span class="mt-4 inline-block text-xs font-medium text-emerald-400">
                Coming soon
            </span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-[#071326] p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 5 6v5c0 4.5 3 7.5 7 10 4-2.5 7-5.5 7-10V6l-7-3Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
                </svg>
            </div>

            <h2 class="font-semibold text-white">Digital Identity</h2>

            <p class="mt-2 text-sm leading-6 text-slate-400">
                Manage your identity verification for barangay services.
            </p>

            <span class="mt-4 inline-block text-xs font-medium text-blue-400">
                Coming soon
            </span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-[#071326] p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/10 text-violet-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M9 13h6M9 17h6" />
                </svg>
            </div>

            <h2 class="font-semibold text-white">My Documents</h2>

            <p class="mt-2 text-sm leading-6 text-slate-400">
                View your submitted requests and related documents.
            </p>

            <span class="mt-4 inline-block text-xs font-medium text-violet-400">
                Coming soon
            </span>
        </div>
    </div>
@endsection