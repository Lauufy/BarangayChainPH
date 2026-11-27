@extends('layouts.legal')

@section('title', 'Identity Verification Privacy Notice')

@section('content')
<div>
    <h1 class="mt-6 text-3xl font-bold text-white">Identity Verification Privacy Notice</h1>
    <p class="mt-2 text-sm text-slate-400">
        BarangayChain PH &mdash; Barangay San Luis, Antipolo City, Rizal
    </p>
    <p class="mt-1 text-xs text-slate-500">
        In Compliance with Republic Act No. 10173 (Data Privacy Act of 2012)
    </p>

    <div class="mt-8 space-y-6 text-sm leading-7 text-slate-300">
        <section>
            <h2 class="text-lg font-semibold text-white">1. Why This Notice Exists</h2>
            <p>
                Before you can use barangay services on <strong>BarangayChain PH</strong>, you must verify your identity. This notice explains what personal data we collect during identity verification, why we collect it, and how we protect it. It supplements our general <a href="{{ route('privacy') }}" class="text-emerald-400 underline">Privacy Policy</a>.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">2. Data We Collect for Identity Verification</h2>
            <p>In line with the principle of proportionality, we collect only what is needed to confirm that you are a real person and the owner of the ID you submit:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-300">
                <li><strong>Personal Details:</strong> Your name, date of birth, address, and other details you enter in the verification form.</li>
                <li><strong>Government-Issued ID:</strong> A photo or scan of your valid ID, and the ID number written on it.</li>
                <li><strong>Selfie Photo:</strong> A photo of your face, taken with your device camera or uploaded, used to compare you with your ID.</li>
                <li><strong>Verification Records:</strong> Your submission dates, review status (pending, verified, or rejected), rejection reasons, and the staff member who reviewed it.</li>
            </ul>
            <p class="mt-2">
                Your ID photo and selfie are <strong>sensitive personal information</strong> and are handled with extra care.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">3. Purpose of Processing</h2>
            <p>Your identity data is used only for the following purposes:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-300">
                <li>Confirming that you are a real person and the rightful owner of the submitted ID;</li>
                <li>Allowing authorized barangay personnel to approve or reject your verification;</li>
                <li>Preventing fake accounts, impersonation, and duplicate use of the same ID; and</li>
                <li>Unlocking access to barangay services such as clearances, permits, and certificates.</li>
            </ul>
            <p class="mt-2">We will not use your ID or selfie for advertising or for any purpose unrelated to barangay services.</p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">4. How We Handle Your ID Number</h2>
            <p>
                To detect duplicate use of the same ID, your ID number is converted into a <strong>one-way hash</strong>. The system compares hashes only, so it can tell that an ID was already used without keeping a readable copy of the number for that check.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">5. Storage, Access, and Security</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-300">
                <li>ID images and selfies are kept in <strong>private storage</strong> and are not publicly accessible.</li>
                <li>Only authorized barangay administrators and staff with the proper permission can view your verification files, and only to review your submission.</li>
                <li>Your files are <strong>never placed on the blockchain</strong>. The blockchain only holds document hashes and approval timestamps for issued permits.</li>
                <li>We use encrypted connections (HTTPS), role-based access control, and audit logs to protect your data.</li>
            </ul>
            <p class="mt-2 rounded-md border border-amber-500/30 bg-amber-500/10 p-3 text-amber-200">
                <strong>Security Reminder:</strong> No online system is completely risk-free. Upload your ID only on a trusted device and network, never share your password or OTP, and make sure you are on the official BarangayChain PH website.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">6. Sharing of Your Data</h2>
            <p>
                We do not sell or trade your identity data. It is shared only with authorized Barangay San Luis personnel performing official duties, or when disclosure is required by law, a court order, or a lawful request from a government agency.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">7. Retention and Disposal</h2>
            <p>
                Your ID photo, selfie, and verification records are kept only as long as needed for the purposes above and in accordance with the records retention schedules of the <em>National Archives of the Philippines (R.A. 9470)</em> and the Commission on Audit (COA). After that period, they are securely deleted or anonymized.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">8. Your Rights as a Data Subject</h2>
            <p>Under the Data Privacy Act of 2012, you have the right to:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-300">
                <li><strong>Be informed</strong> about how your identity data is used;</li>
                <li><strong>Access</strong> the identity information we hold about you;</li>
                <li><strong>Correct</strong> wrong or outdated details;</li>
                <li><strong>Object, or request blocking or erasure</strong>, subject to legal record-keeping duties; and</li>
                <li><strong>File a complaint</strong> with the National Privacy Commission (NPC).</li>
            </ul>
            <p class="mt-2">
                If you withdraw your consent or ask for your identity data to be deleted, you may lose access to services that require a verified identity.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">9. Data Protection Officer (DPO)</h2>
            <p>To use your rights or ask a question about this notice, contact our Data Protection Officer:</p>
            <div class="mt-2 rounded-md border border-slate-800 bg-slate-900/60 p-4 text-slate-300">
                <p><strong>Data Protection Officer &mdash; BarangayChain PH</strong></p>
                <p>Office of the Punong Barangay / Barangay Secretary</p>
                <p>Barangay San Luis Hall, Antipolo City, Rizal, Philippines</p>
            </div>
        </section>
    </div>

    <div class="mt-8 border-t border-slate-800 pt-5 text-xs text-slate-400">
        <p>
            <strong>Consent Notice:</strong> By submitting your identity verification, you confirm that you have read this notice and consent to the processing of your personal data as described above.
        </p>
    </div>
</div>
@endsection