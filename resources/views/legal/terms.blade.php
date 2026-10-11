
@extends('layouts.guest')

@section('title', 'Privacy Policy')

@section('content')
<div class="mx-auto max-w-3xl rounded-2xl border border-slate-800 bg-[#071326] p-6 text-slate-300 sm:p-10">
    <a href="{{ route('register') }}" class="text-sm text-emerald-400 hover:text-emerald-300">
        &larr; Back to Registration
    </a>

    <h1 class="mt-6 text-3xl font-bold text-white">Privacy Policy</h1>
    <p class="mt-2 text-sm text-slate-500">BarangayChain PH</p>

    <div class="mt-8 space-y-6 text-sm leading-7">
        <section>
            <h2 class="text-lg font-semibold text-white">1. Information We Collect</h2>
            <p>BarangayChain PH may collect your name, email address, account credentials, contact details, submitted documents, and information required to process barangay services.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">2. How We Use Your Information</h2>
            <p>Your information may be used to manage your account, verify your identity, process permits and clearances, send service notifications, and maintain system security.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">3. Data Protection</h2>
            <p>Reasonable organizational, technical, and security measures should be implemented to protect personal information against unauthorized access, disclosure, alteration, or loss.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">4. Sharing of Information</h2>
            <p>Personal information should only be accessed or shared by authorized personnel and appropriate service providers when necessary for legitimate service purposes or as required by law.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">5. Data Retention</h2>
            <p>Personal information should be retained only as long as necessary for the stated purposes and applicable legal or records-management requirements.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">6. Your Privacy Rights</h2>
            <p>You may have rights to access, correct, or request the appropriate handling of your personal information under applicable data protection laws, subject to lawful limitations.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">7. Contact Information</h2>
            <p>For privacy-related questions or requests, contact the authorized Barangay San Luis office through its official communication channels.</p>
        </section>
    </div>

    <p class="mt-8 border-t border-slate-800 pt-5 text-xs text-slate-500">
        Draft policy for BarangayChain PH. Have the barangay's designated privacy officer or responsible officials review it for compliance with the Philippine Data Privacy Act before production use.
    </p>
</div>
@endsection
