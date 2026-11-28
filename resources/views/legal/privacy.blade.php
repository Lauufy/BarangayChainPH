@extends('layouts.legal')

@section('title', 'Privacy Policy')

@section('content')
<div>
    <h1 class="mt-6 text-3xl font-bold text-white">Privacy Policy</h1>
    <p class="mt-2 text-sm text-slate-400">
        BarangayChain PH &mdash; Barangay San Luis, Antipolo City, Rizal
    </p>
    <p class="mt-1 text-xs text-slate-500">
        In Compliance with Republic Act No. 10173 (Data Privacy Act of 2012)
    </p>

    <div class="mt-8 space-y-6 text-sm leading-7 text-slate-300">
        <section>
            <h2 class="text-lg font-semibold text-white">1. Commitment to Data Privacy</h2>
            <p>
                <strong>Barangay San Luis, Antipolo City</strong> ("we," "us," or "the Barangay"), as the Personal Information Controller (PIC) for <strong>BarangayChain PH</strong>, respects and upholds your fundamental right to privacy under <em>Republic Act No. 10173</em>, otherwise known as the <strong>Data Privacy Act of 2012 (DPA)</strong>, its Implementing Rules and Regulations (IRR), and issuances of the National Privacy Commission (NPC). This policy explains how we collect, process, store, verify, and protect your personal data.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">2. Personal Information We Collect</h2>
            <p>
                In adherence to the principle of proportionality, we collect only data necessary to verify residency and deliver legitimate barangay services:
            </p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-300">
                <li><strong>Mandatory Account & Residency Information:</strong> Full name, email address, mobile phone number, date of birth, civil status, nationality, and complete residential address (house/lot/block number, street/purok/sitio, barangay, city, province, and ZIP code).</li>
                <li><strong>Optional Demographic Information:</strong> Place of birth, occupation, sex/gender, and religion are optional (religious affiliation is used solely for aggregated barangay demographic profiling and social welfare planning).</li>
                <li><strong>Service & Application Records:</strong> Uploaded valid government IDs, proof of residency, business details, cedula/community tax numbers, purpose of clearance/permit, and transaction timestamps.</li>
                <li><strong>Technical & Audit Logs:</strong> IP addresses, device/browser metadata, login timestamps, and system audit trails required for cybersecurity monitoring.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">3. Purpose and Legal Basis for Processing</h2>
            <p>
                Your personal data is processed pursuant to statutory mandates under the <em>Local Government Code of 1991 (R.A. 7160)</em> and your explicit consent for the following purposes:
            </p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-300">
                <li>Authenticating citizen identity and verifying residency within Barangay San Luis;</li>
                <li>Processing, evaluating, and issuing Barangay Clearances, Business Permits, Certificates of Indigency, and other official documents;</li>
                <li>Generating tamper-proof cryptographic verification records on our blockchain ledger;</li>
                <li>Sending real-time SMS/email status notifications regarding your applications; and</li>
                <li>Generating anonymized statistical analytics to improve local public service delivery.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">4. How Blockchain Technology Protects Your Privacy</h2>
            <p>
                Because blockchain ledgers are immutable, <strong>BarangayChain PH never stores your raw personal details, home address, or uploaded IDs directly on the blockchain</strong>. Instead, personal data is kept in secured off-chain government databases, while only non-reversible <strong>cryptographic hashes</strong>, document reference IDs, and approval timestamps are anchored on-chain. This ensures third parties can verify a permit's authenticity without exposing your private information.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">5. Security Measures and User Caution Advisory</h2>
            <p>
                We enforce organizational, physical, and technical safeguards—including role-based access control (RBAC), encrypted data transmission (HTTPS/TLS), hashed passwords, and immutable audit logs—to protect your data against unauthorized access, alteration, or loss.
            </p>
            <p class="mt-2 rounded-md border border-amber-500/30 bg-amber-500/10 p-3 text-amber-200">
                <strong>Important Security Caution:</strong> While we continuously strive to protect your information, no web-based platform or electronic transmission is completely immune to cyberattacks, phishing, or unauthorized interception. We urge all residents to exercise caution: never share your login password or OTPs with anyone (including barangay staff), avoid accessing your account on public/unsecured Wi-Fi networks, and always verify that you are on the official BarangayChain PH domain before submitting sensitive documents.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">6. Data Sharing and Disclosure</h2>
            <p>
                We do not sell, rent, or trade your personal information. Your data is accessible only to authorized Barangay San Luis personnel performing official duties. Information may only be disclosed externally when: (a) verifying document authenticity through QR/hash lookup, which shows the document's validity status and limited permit details (document type, permit number, reference number, the holder's first name with the rest of the name masked, issue and expiry dates, issuing office, and version), (b) required by law, court order, or lawful subpoena, or (c) necessary for official coordination with the City Government of Antipolo or national government agencies under valid data-sharing agreements.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">7. Data Retention and Disposal</h2>
            <p>
                Personal data and transaction records are retained only for as long as necessary to fulfill the declared civic purposes and in accordance with the records retention schedules prescribed by the <em>National Archives of the Philippines (R.A. 9470)</em> and the Commission on Audit (COA). Once retention periods expire, records are securely purged or anonymized.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">8. Your Rights as a Data Subject</h2>
            <p>
                Under Section 16 of the Data Privacy Act of 2012, you have the right to:
            </p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-300">
                <li><strong>Be Informed</strong> about how your personal data is collected and processed;</li>
                <li><strong>Access</strong> a copy of your personal information held in our system;</li>
                <li><strong>Rectify or Correct</strong> inaccurate, outdated, or incomplete profile records;</li>
                <li><strong>Object to Processing or Request Erasure/Blocking</strong> of personal data, subject to statutory public record-keeping obligations; and</li>
                <li><strong>Lodge a Complaint</strong> with the National Privacy Commission (NPC) if you believe your data privacy rights have been violated.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">9. Data Protection Officer (DPO) Contact Details</h2>
            <p>
                To exercise your data privacy rights, report a suspected security incident, or submit privacy inquiries, please contact our designated Data Protection Officer:
            </p>
            <div class="mt-2 rounded-md border border-slate-800 bg-slate-900/60 p-4 text-slate-300">
                <p><strong>Data Protection Officer &mdash; BarangayChain PH</strong></p>
                <p>Office of the Punong Barangay / Barangay Secretary</p>
                <p>Barangay San Luis Hall, Antipolo City, Rizal, Philippines</p>
                <p>Official Channels: Barangay San Luis Helpdesk &amp; Official Municipal/Barangay Communication Channels</p>
            </div>
        </section>
    </div>

    <div class="mt-8 border-t border-slate-800 pt-5 text-xs text-slate-400">
        <p>
            <strong>Compliance Statement:</strong> Prepared for BarangayChain PH (Barangay San Luis, Antipolo City) in accordance with R.A. 10173 (Data Privacy Act of 2012). Subject to periodic review by the Barangay Data Protection Officer.
        </p>
    </div>
</div>
@endsection