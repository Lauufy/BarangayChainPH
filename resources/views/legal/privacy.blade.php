
@extends('layouts.guest')

@section('title', 'Terms of Service')

@section('content')
<div class="mx-auto max-w-3xl rounded-2xl border border-slate-800 bg-[#071326] p-6 text-slate-300 sm:p-10">
    <a href="{{ route('register') }}" class="text-sm text-emerald-400 hover:text-emerald-300">
        &larr; Back to Registration
    </a>

    <h1 class="mt-6 text-3xl font-bold text-white">Terms of Service</h1>
    <p class="mt-2 text-sm text-slate-500">BarangayChain PH</p>

    <div class="mt-8 space-y-6 text-sm leading-7">
        <section>
            <h2 class="text-lg font-semibold text-white">1. Acceptance of Terms</h2>
            <p>By creating an account, you agree to follow these terms when using BarangayChain PH and its barangay services.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">2. Account Information</h2>
            <p>You must provide accurate and updated information. You are responsible for keeping your account credentials confidential and for activities performed through your account.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">3. Proper Use</h2>
            <p>You must provide truthful information and submit only legitimate requests and documents. Fraudulent submissions, misuse, or unauthorized access are prohibited.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">4. Service Availability</h2>
            <p>Permit processing, document verification, notifications, and other services are subject to barangay procedures, staff review, and system availability.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">5. Account Suspension</h2>
            <p>Accounts may be restricted when necessary to protect system security or address suspected misuse, subject to applicable policies and laws.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">6. Changes to These Terms</h2>
            <p>These terms may be updated when necessary. Users should review the latest version before continuing to use the service.</p>
        </section>
    </div>

    <p class="mt-8 border-t border-slate-800 pt-5 text-xs text-slate-500">
        Draft policy for BarangayChain PH. Have the responsible barangay officials review and approve it before production use.
    </p>
</div>
@endsection
