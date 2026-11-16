@extends('layouts.legal')

@section('title', 'Terms of Service')

@section('content')
<div>
    <h1 class="mt-6 text-3xl font-bold text-white">Terms of Service</h1>
    <p class="mt-2 text-sm text-slate-400">
        BarangayChain PH &mdash; Barangay San Luis, Antipolo City, Rizal
    </p>
    <p class="mt-1 text-xs text-slate-500">
        Effective Date: November 2026 | Governing Jurisdiction: Republic of the Philippines
    </p>

    <div class="mt-8 space-y-6 text-sm leading-7 text-slate-300">
        <section>
            <h2 class="text-lg font-semibold text-white">1. Acceptance of Terms and Scope</h2>
            <p>
                Welcome to <strong>BarangayChain PH</strong>, the official blockchain-enabled intelligent e-governance platform developed for <strong>Barangay San Luis, Antipolo City</strong>. By registering an account, accessing the portal, or submitting requests for barangay permits, clearances, and public services, you agree to be legally bound by these Terms of Service, our Privacy Policy, and applicable Philippine laws, including the <em>Electronic Commerce Act of 2000 (R.A. 8792)</em> and the <em>Ease of Doing Business and Efficient Government Service Delivery Act (R.A. 11032)</em>.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">2. Citizen Account Registration and Responsibility</h2>
            <p>
                To access digital public services, you must provide true, accurate, current, and complete residency and identity details. You are solely responsible for maintaining the confidentiality of your password, one-time passwords (OTPs), and account credentials. Any transaction, application, or document request initiated through your authenticated account is presumed to be authorized by you. Notify the Barangay San Luis Helpdesk immediately if you suspect unauthorized access.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">3. Lawful Use and Prohibition of Falsification</h2>
            <p>
                You agree to use BarangayChain PH strictly for legitimate civic transactions. Submitting falsified identification, tampered supporting documents, fraudulent business information, or impersonating another resident is strictly prohibited. Violations may result in immediate account termination, revocation of issued permits, and criminal prosecution under the <em>Revised Penal Code</em> (Falsification of Public Documents) and the <em>Cybercrime Prevention Act of 2012 (R.A. 10175)</em>.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">4. Blockchain Verification and Intelligent Processing</h2>
            <p>
                BarangayChain PH utilizes distributed ledger (blockchain) technology and automated intelligent validation to enhance document authenticity, auditability, and processing speed. While cryptographic hashes and verification timestamps recorded on the blockchain are tamper-evident and immutable, the <strong>final approval, issuance, or denial of any barangay clearance, business permit, or certification remains subject to official verification and administrative authority of Barangay San Luis personnel</strong>.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">5. Security Posture and User Caution Disclaimer</h2>
            <p>
                We implement industry-standard encryption, access controls, and blockchain integrity checks to safeguard system operations. However, <strong>no online platform or digital network can guarantee 100% absolute security against sophisticated cyber threats, network interruptions, or zero-day vulnerabilities</strong>. Users are strongly advised to exercise personal caution: access the portal only via trusted devices and secure internet connections, avoid sharing credentials or verification codes, and verify official QR/hash certificates strictly through the official BarangayChain PH verification portal.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">6. Service Availability and Modifications</h2>
            <p>
                Barangay San Luis strives to maintain continuous portal availability; however, access may be temporarily suspended for scheduled maintenance, blockchain node synchronization, system upgrades, or force majeure events. In the event of prolonged system downtime, residents may process urgent requests directly at the Barangay San Luis Hall.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">7. Account Suspension and Termination</h2>
            <p>
                Authorized system administrators reserve the right to restrict, suspend, or revoke account access without prior notice if there is reasonable suspicion of security breaches, fraudulent submissions, automated scraping, or misuse that compromises public service delivery.
            </p>
        </section>

        <section>
            <h2 class="text-lg font-semibold text-white">8. Amendments to Terms</h2>
            <p>
                Barangay San Luis may revise these Terms of Service to reflect updates in local ordinances, national e-governance directives, or system capabilities. Continued use of BarangayChain PH following the posting of updated terms constitutes acceptance of those changes.
            </p>
        </section>
    </div>

    <div class="mt-8 border-t border-slate-800 pt-5 text-xs text-slate-400">
        <p>
            <strong>Official Governance Notice:</strong> BarangayChain PH &bull; Barangay San Luis, Antipolo City. For technical assistance or legal inquiries regarding digital permits, please visit the Barangay Hall or contact the Office of the Barangay Secretary.
        </p>
    </div>
</div>
@endsection