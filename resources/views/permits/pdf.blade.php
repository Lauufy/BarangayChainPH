<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }}</title>

    <style>
        @page {
            margin: 55px 65px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #334155;
            margin: 0;
        }

        /* Official document border */
        .frame {
            position: fixed;
            top: -35px;
            left: -40px;
            right: -40px;
            bottom: -35px;
            border: 3px double #17325c;
        }

        .header {
            text-align: center;
            color: #17325c;
        }

        .country {
            margin: 0;
            font-size: 9px;
            letter-spacing: 1.5px;
        }

        .barangay {
            margin: 5px 0 0;
            font-size: 20px;
            font-weight: bold;
        }

        .city {
            margin: 0;
            font-size: 10px;
            color: #64748b;
        }

        .header-line {
            width: 100%;
            margin: 15px 0;
            border-top: 2px solid #17325c;
        }

        .header-accent {
            width: 65px;
            margin: 10px auto;
            border-top: 4px solid #16a36a;
        }

        .document-title {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #17325c;
            text-transform: uppercase;
        }

        .subtitle {
            margin: 5px 0 0;
            font-size: 9px;
            letter-spacing: 1.5px;
            color: #64748b;
            text-transform: uppercase;
        }

        /* Document information */
        .meta {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }

        .meta td {
            width: 33.33%;
            padding: 10px;
            text-align: center;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
        }

        .meta-label {
            display: block;
            font-size: 8px;
            letter-spacing: 1px;
            color: #64748b;
            text-transform: uppercase;
        }

        .meta-value {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            font-weight: bold;
            color: #17325c;
        }

        /* Certificate content */
        .content {
            margin-top: 35px;
            text-align: justify;
        }

        .salutation {
            margin-bottom: 22px;
            font-weight: bold;
            color: #17325c;
        }

        .content p {
            margin: 0 0 17px;
        }

        .applicant {
            font-weight: bold;
            color: #17325c;
            text-transform: uppercase;
        }

        .purpose {
            margin: 25px 0;
            padding: 13px 15px;
            background: #f1f5f9;
            border-left: 4px solid #16a36a;
        }

        .purpose-label {
            display: block;
            margin-bottom: 5px;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #64748b;
            text-transform: uppercase;
        }

        .purpose-value {
            font-size: 11px;
            font-weight: bold;
            color: #17325c;
        }

        .closing {
            margin-top: 25px !important;
        }

        /* Signature and QR */
        .signature {
            width: 100%;
            margin-top: 55px;
            border-collapse: collapse;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }

        .qr img {
            width: 100px;
            height: 100px;
        }

        .qr-caption {
            margin-top: 6px;
            font-size: 8px;
            color: #64748b;
        }

        .signature-line {
            width: 85%;
            margin: 0 auto 7px;
            border-top: 1px solid #17325c;
        }

        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #17325c;
        }

        .signature-role {
            font-size: 9px;
            color: #64748b;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 2px solid #17325c;
            text-align: center;
            font-size: 8px;
            color: #64748b;
        }

        .footer-brand {
            font-weight: bold;
            color: #17325c;
        }

        .footer-accent {
            color: #16a36a;
        }
    </style>
</head>

<body>

    <div class="frame"></div>

    {{-- Official Letterhead --}}
    <div class="header">
        <p class="country">REPUBLIC OF THE PHILIPPINES</p>
        <p class="country">PROVINCE OF RIZAL</p>
        <p class="barangay">BARANGAY SAN LUIS</p>
        <p class="city">Antipolo City, Rizal</p>

        <div class="header-line"></div>

        <h1 class="document-title">{{ $documentTitle }}</h1>
        <p class="subtitle">Official Barangay Government Document</p>

        <div class="header-accent"></div>
    </div>

    {{-- Document Information --}}
    <table class="meta">
        <tr>
            <td>
                <span class="meta-label">Document Number</span>
                <span class="meta-value">
                    {{ $document->document_number }}
                </span>
            </td>

            <td>
                <span class="meta-label">Reference Number</span>
                <span class="meta-value">
                    {{ $application->reference_number }}
                </span>
            </td>

            <td>
                <span class="meta-label">Date Issued</span>
                <span class="meta-value">
                    {{ $issuedAt->format('M j, Y') }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Certification --}}
    <div class="content">

        <p class="salutation">TO WHOM IT MAY CONCERN:</p>

        <p>
            This is to officially certify that
            <strong class="applicant">
                {{ $application->applicant_name }}
            </strong>,
            {{ $application->age }} years old, is a bona fide
            resident of
            <strong class="applicant">
                {{ $application->address }}
            </strong>,
            within the jurisdiction of Barangay San Luis,
            Antipolo City, Rizal.
        </p>

        <p>
            This document is issued by the Office of the Barangay
            upon the request of the above-named individual,
            subject to the information and records available
            to this office.
        </p>

        {{-- Declared Purpose --}}
        <div class="purpose">
            <span class="purpose-label">Purpose of Issuance</span>
            <span class="purpose-value">
                {{ $application->purpose }}
            </span>
        </div>

        <p class="closing">
            This certification is issued upon the request of
            the above-named individual for whatever lawful
            purpose it may serve.
        </p>

        <p>
            Issued this {{ $issuedAt->format('jS') }} day of
            {{ $issuedAt->format('F Y') }} at Barangay San Luis,
            Antipolo City, Province of Rizal, Philippines.
        </p>

    </div>

    {{-- QR Verification and Authorized Signature --}}
    <table class="signature">
        <tr>
            <td class="qr">
                @if (!empty($qrCode))
                    <img src="{{ $qrCode }}" alt="Verification QR Code">

                    <div class="qr-caption">
                        Scan to verify document authenticity
                    </div>
                @else
                    <div class="qr-caption">
                        QR code unavailable.
                    </div>
                @endif
            </td>

            <td>
                <div class="signature-line"></div>

                <div class="signature-name">
                    Barangay Official
                </div>

                <div class="signature-role">
                    Authorized Signatory
                </div>
            </td>
        </tr>
    </table>

    {{-- Official Footer --}}
    <div class="footer">
        <span class="footer-brand">BARANGAYCHAIN PH</span>
        <span class="footer-accent"> | </span>
        Secure Digital Document Authentication

        <br>

        Official Document of Barangay San Luis,
        Antipolo City, Rizal
    </div>

</body>
</html>