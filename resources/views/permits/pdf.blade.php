<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }}</title>

    <style>
        @page { margin: 42pt 52pt; }

        body {
            margin: 0;
            color: #243247;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5pt;
            line-height: 1.48;
        }

        .frame {
            position: fixed;
            top: -23pt;
            right: -29pt;
            bottom: -23pt;
            left: -29pt;
            border: 0.75pt solid #b4c0cf;
        }

        .watermark {
            position: fixed;
            top: 45%;
            right: 0;
            left: 0;
            margin-top: -132pt;
            text-align: center;
        }

        .watermark img {
            width: 264pt;
            height: 264pt;
            opacity: 0.07;
        }

        .letterhead {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5pt solid #17325c;
        }

        .letterhead td {
            padding: 0 0 12pt;
            vertical-align: middle;
        }

        .letterhead .mark { width: 67pt; }
        .mark img { width: 54pt; height: 54pt; }

        .jurisdiction {
            margin: 0;
            color: #45566c;
            font-size: 8pt;
        }

        .barangay {
            margin: 1pt 0;
            color: #17325c;
            font-size: 17pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .document-title {
            margin: 16pt 0 10pt;
            color: #17325c;
            font-family: DejaVu Serif, serif;
            font-size: 22pt;
            line-height: 1.18;
        }

        .meta {
            margin: 0 0 16pt;
            padding: 8pt 11pt;
            border-left: 2pt solid #2c7564;
            background: #eff3f7;
            font-size: 8.5pt;
            line-height: 1.35;
        }

        .meta strong { color: #17325c; }
        .content p { margin: 0 0 8pt; }

        .salutation, .applicant {
            color: #17325c;
            font-weight: bold;
        }

        .purpose {
            margin: 10pt 0;
            padding: 7pt 11pt;
            border: 0.75pt solid #c4d0dd;
            page-break-inside: avoid;
        }

        .purpose-label {
            display: block;
            color: #45566c;
            font-size: 8pt;
            font-weight: bold;
        }

        .purpose-value { color: #17325c; }

        .signature {
            width: 100%;
            margin-top: 16pt;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .signature td {
            width: 50%;
            vertical-align: bottom;
        }

        .qr img { width: 71pt; height: 71pt; }

        .qr-caption, .signature-role {
            margin-top: 2pt;
            color: #45566c;
            font-size: 8pt;
        }

        .signatory { text-align: center; }

        .signature-line {
            margin: 0 0 4pt;
            border-top: 0.75pt solid #17325c;
        }

        .signature-name, .footer-brand {
            color: #17325c;
            font-weight: bold;
        }

        .footer {
            margin-top: 13pt;
            padding-top: 6pt;
            border-top: 0.75pt solid #b4c0cf;
            color: #45566c;
            font-size: 8pt;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    @php
        $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/branding/barangaychain-lg1.png')));
    @endphp

    <div class="frame"></div>
    <div class="watermark"><img src="{{ $logo }}" alt=""></div>

    <table class="letterhead">
        <tr>
            <td class="mark"><img src="{{ $logo }}" alt="BarangayChain PH"></td>
            <td>
                <p class="jurisdiction">Republic of the Philippines</p>
                <p class="barangay">Barangay San Luis</p>
                <p class="jurisdiction">Antipolo City, Province of Rizal</p>
            </td>
        </tr>
    </table>

    <h1 class="document-title">{{ $documentTitle }}</h1>

    {{-- Labeled permit fields support text extraction and OCR. --}}
    <div class="meta">
        @foreach (\App\Services\PermitFields::LABELS as $key => $label)
            <div><strong>{{ $label }}:</strong> {{ $document->canonical_fields[$key] }}</div>
        @endforeach
        <div><strong>Reference Number:</strong> {{ $application->reference_number }}</div>
    </div>

    {{-- Certification --}}
    <div class="content">

        <p class="salutation">TO WHOM IT MAY CONCERN:</p>

        <p>
            This is to officially certify that
            <strong class="applicant">{{ $application->applicant_name }}</strong>,
            {{ $application->age }} years old, is a bona fide
            resident of
            <strong class="applicant">{{ $application->address }}</strong>,
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

        <p>
            This certification is issued upon the request of
            the above-named individual for whatever lawful
            purpose it may serve.
        </p>

        <p>
            Issued this {{ $issuedAt->format('jS') }} day of
            {{ $issuedAt->format('F Y') }} at Barangay San Luis,
            Antipolo City, Province of Rizal, Philippines.
        </p>

        @if ($document->expires_at)
            <p>
                This certification is valid until
                <strong>{{ $document->expires_at->format('F j, Y') }}</strong>.
            </p>
        @endif

    </div>

    {{-- QR Verification and Authorized Signature --}}
    <table class="signature">
        <tr>
            <td class="qr">
                @if (!empty($qrCode))
                    <img src="{{ $qrCode }}" alt="Verification QR Code">

                    <div class="qr-caption">
                        Scan to upload and verify this PDF
                    </div>
                @else
                    <div class="qr-caption">
                        QR code unavailable.
                    </div>
                @endif
            </td>

            <td class="signatory">
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

    <div class="footer">
        <span class="footer-brand">BarangayChain PH</span> &middot; Official document of Barangay San Luis
    </div>

</body>

</html>
