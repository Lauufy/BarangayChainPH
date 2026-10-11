@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-medium text-emerald-400">ADMINISTRATION PORTAL</p>

        <h1 class="mt-2 text-3xl font-bold tracking-tight text-white">
            Welcome, {{ auth()->user()->name }}!
        </h1>

        <p class="mt-2 text-slate-400">
            Manage system accounts and oversee barangay digital services.
        </p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-[#071326] p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-3v6m3-3h-6" />
                </svg>
            </div>

            <h2 class="font-semibold text-white">User & Role Management</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">
                Create staff accounts and manage user roles and access.
            </p>
            <a href="{{ route('admin.users.index') }}"
                class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-400 transition hover:text-emerald-300">
                Manage Accounts
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                </svg>
            </a>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-[#071326] p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 7h8m-8 4h8m-8 4h5M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                </svg>
            </div>

            <h2 class="font-semibold text-white">Permit Oversight</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">
                Monitor permit applications and their processing status.
            </p>
            <span class="mt-4 inline-block text-xs font-medium text-blue-400">Coming soon</span>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-[#071326] p-6">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500/10 text-violet-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-5m4 5V7m4 9v-8" />
                </svg>
            </div>

            <h2 class="font-semibold text-white">Reports & Analytics</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">
                Review service activity and system performance.
            </p>
            <span class="mt-4 inline-block text-xs font-medium text-violet-400">Coming soon</span>
        </div>
    </div>
@endsection
