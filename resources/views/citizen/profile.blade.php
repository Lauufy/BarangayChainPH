@extends('layouts.citizen')

@section('title', 'My Profile')

@section('content')
    @php
        $user = auth()->user();
        $profile = $user->citizenProfile;
        $addressParts = array_filter([
            $profile?->house_number,
            $profile?->street,
            $profile?->barangay,
            $profile?->city_municipality,
            $profile?->province,
            $profile?->zip_code,
        ]);
    @endphp

    <div class="space-y-7">
        <section>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Account</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#09172D] sm:text-3xl">My profile</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Review the account and contact information currently associated with your citizen portal.</p>
        </section>

        <div class="grid gap-5 lg:grid-cols-[1.3fr_0.7fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-xl font-bold text-emerald-700">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-bold text-[#09172D]">{{ $user->name }}</h2>
                        <p class="mt-1 break-all text-sm text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>

                <dl class="grid gap-5 pt-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-800">{{ $profile?->phone ?: 'Not added' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date of birth</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-800">{{ $profile?->date_of_birth?->format('M j, Y') ?? 'Not added' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Address</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-800">{{ $addressParts ? implode(', ', $addressParts) : 'Not added' }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="rounded-2xl border border-blue-100 bg-blue-50 p-5 sm:p-6">
                <h2 class="font-semibold text-[#09172D]">Need to update your information?</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Profile editing is not available online yet. Contact your barangay office for assistance updating your citizen record.</p>
                <a href="{{ route('privacy') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-900">Read our privacy notice <span aria-hidden="true">→</span></a>
            </aside>
        </div>
    </div>
@endsection
