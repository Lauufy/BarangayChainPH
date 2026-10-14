@extends('layouts.staff')

@section('title', 'Staff Dashboard')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-medium text-emerald-400">STAFF PORTAL</p>

        <h1 class="mt-2 text-3xl font-bold tracking-tight text-white">
            Welcome, {{ auth()->user()->name }}!
        </h1>

        <p class="mt-2 text-slate-400">
            Manage citizen requests and assist with barangay services.
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
                Review and process submitted permit and clearance applications.
            </p>
            <span class="mt-4 inline-block text-xs font-medium text-emerald-400">Coming soon</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-[#071326] p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8m-8 4h8m-8 4h5M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                </svg>
            </div>

            <h2 class="font-semibold text-white">Document Review</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">
                Check submitted documents and assist with verification.
            </p>
            <span class="mt-4 inline-block text-xs font-medium text-blue-400">Coming soon</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-[#071326] p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/10 text-violet-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-3v6m3-3h-6" />
                </svg>
            </div>

            <h2 class="font-semibold text-white">Citizen Assistance</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">
                Help citizens with their accounts and service requests.
            </p>
            <span class="mt-4 inline-block text-xs font-medium text-violet-400">Coming soon</span>
        </div>
    </div>
@endsection