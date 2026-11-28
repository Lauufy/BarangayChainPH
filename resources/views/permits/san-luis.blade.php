@php
    $business = $type === 'business_clearance';
    $indigency = $type === 'certificate_of_indigency';
    $purposes = config('barangay_services.services.certificate-of-indigency.fields.0.options');
    $draft = !isset($document);
    $field = fn($key) => $draft ? "[[{$key}]]" : $details[$key] ?? '________';
    $date = $draft
        ? '_____ day of __________, _____'
        : $issuedAt->format('jS') . ' day of ' . $issuedAt->format('F, Y');
    $title = $business ? 'BUSINESS CLEARANCE' : ($indigency ? 'CERTIFICATE OF INDIGENCY' : 'CERTIFICATION');
    $branding = fn($file) => $draft
        ? asset('img/branding/' . $file)
        : 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('img/branding/' . $file)));
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            margin: 0;
            color: #101010;
            background: white;
            font: 10.5pt/1.45 Helvetica, Arial, sans-serif;
        }

        .seal {
            position: absolute;
            left: 3.7%;
            top: 164pt;
            width: 92.6%;
            height: 516pt;
            opacity: .055;
        }

        .sheet {
            position: relative;
            margin: 0 auto;
            width: 100%;
        }

        .banner {
            display: block;
            width: 92%;
            margin: 25pt 4% 0;
        }

        header {
            text-align: center;
            margin: 39pt 5% 0;
            color: #333;
        }

        h1 {
            margin: 0;
            font: bold 23pt/1.15 "DejaVu Serif", Georgia, serif;
        }

        .reference {
            margin-top: 5pt;
            font-size: 10pt;
        }

        main {
            margin: 41pt 10% 0;
        }

        p {
            margin: 0;
            text-align: justify;
            letter-spacing: .65pt;
            line-height: 1.72;
            overflow-wrap: break-word;
        }

        .identity {
            min-height: 72pt;
        }

        .general .identity {
            min-height: 98pt;
        }

        .purpose {
            min-height: 52pt;
            margin-top: 24pt;
        }

        .choices {
            margin: 2pt 0 16pt 15.5%;
        }

        .choices div {
            min-height: 19pt;
        }

        .box {
            display: inline-block;
            width: 12pt;
            height: 12pt;
            line-height: 12pt;
            border: .85pt solid black;
            border-radius: 2.5pt;
            text-align: center;
            vertical-align: middle;
            font: bold 10pt Helvetica, sans-serif;
            margin-right: 5pt;
            color: black;
        }

        .attestation {
            margin-top: 34pt;
        }

        .officials {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14pt;
        }

        td {
            padding: 0;
            vertical-align: bottom;
        }

        .captain {
            text-align: center;
            width: 125pt;
        }

        .lower {
            width: 100%;
            border-collapse: collapse;
        }

        .qr {
            text-align: center;
            font-size: 8.5pt;
        }

        .qr img,
        .draft-qr {
            display: block;
            width: 96pt;
            height: 96pt;
            margin: 0 auto;
            padding: 5pt;
            background: white;
        }

        .draft-qr {
            border: .6pt dashed #aaa;
            font-size: 8pt;
            line-height: 86pt;
            color: #666;
        }

        .client {
            width: 38%;
            text-align: center;
        }

        .indigency .lower {
            margin-top: 29pt;
        }

        .indigency .qr {
            width: 115pt;
        }

        .general .lower {
            margin-top: 14pt;
        }

        .general .client {
            vertical-align: middle;
            border: 0;
        }

        .client div {
            border-top: .75pt solid black;
            padding-top: 4pt;
        }

        .note {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            color: #df422f;
            margin-top: 13pt;
        }

        .business main {
            margin-top: 22pt;
        }

        .intro,
        .checks,
        .business-field {
            text-align: center;
            font-weight: bold;
        }

        .intro {
            letter-spacing: .5pt;
        }

        .checks {
            margin: 19pt auto 15pt;
            color: #e43618;
        }

        .business-field {
            width: 57%;
            margin: 0 auto 11pt;
            overflow-wrap: break-word;
        }

        .line {
            min-height: 21pt;
            border-bottom: .75pt solid black;
            margin-bottom: 3pt;
        }

        .findings {
            margin-top: 15pt;
        }

        ul {
            margin: 7pt 6% 18pt 10%;
            padding-left: 16pt;
        }

        li {
            padding-bottom: 5pt;
        }

        .business p {
            line-height: 1.45;
            letter-spacing: .1pt;
        }

        .business .lower {
            margin-top: 16pt;
        }

        .business .lower td {
            width: 33.33%;
        }

        .records {
            font-size: 9.5pt;
        }

        footer {
            margin: 6pt 0 0;
            text-align: center;
            font: bold 8pt Helvetica, Arial, sans-serif;
            color: #999;
        }

        .checks .option {
            display: inline-block;
            margin: 0 17pt;
        }
    </style>
</head>

<body class="{{ $business ? 'business' : ($indigency ? 'indigency' : 'general') }}">
    <img class="seal" src="{{ $branding('barangayChainPH-logo.png') }}" alt="">
    <div class="sheet">
        <img class="banner" src="{{ $branding('header_permit_new.png') }}"
            alt="Barangay San Luis, Antipolo City, Province of Rizal">
        <header>
            <h1>{{ $title }}</h1>
            <div class="reference">REFERENCE NUMBER:
                <strong>{{ $draft ? '______________________' : $application->reference_number }}</strong></div>
        </header>
        <main>
            @if ($business)
                <div class="intro">This is to certify that the Business / Trade Activity stated below:</div>
                <div class="checks">
                    @foreach (['New application' => 'NEW', 'Renewal' => 'RENEWAL'] as $choice => $label)
                        <span class="option"><b class="box"
                                data-choice="{{ $choice }}">{{ ($details['application_type'] ?? '') === $choice ? 'X' : '' }}</b>
                            {{ $label }}</span>
                    @endforeach
                </div>
                <div class="business-field">
                    <div class="line">{{ $field('business_name') }}</div>(Business Name / Trade Activity)
                </div>
                <div class="business-field">
                    <div class="line">{{ $field('business_address') }}</div>Barangay San Luis, Antipolo City,
                    Rizal<br>(Business Address)
                </div>
                <div class="business-field">
                    <div class="line">{{ $field('business_owner_name') }}</div>(Owner/Manager/Operator)
                </div>
                <div class="business-field">
                    <div class="line">{{ $field('nature_of_business') }}</div>
                </div>
                <div class="findings">is applying for a Barangay Business Clearance to secure corresponding Mayor’s
                    Permit and has been found to be:</div>
                <ul>
                    <li>In conformity with the provisions of existing Barangay Ordinance, Rules and Regulations;</li>
                    <li>Not among those Businesses of Trade Activities banned in the Barangay;</li>
                </ul>
                <p>Issued this {{ $date }} at Barangay San Luis, Antipolo City, Province of Rizal, for whatever
                    legal purpose it may serve.</p>
            @else
                <p class="identity">This is to certify that <u>{{ $name }}</u>, <u>{{ $age }}</u>
                    years of age, {{ $nationality }}, and presently residing at <u>{{ $address }}</u>,
                    @if ($indigency)
                        is an indigent with no known source of income or livelihood.
                    @else
                        has lived in Barangay San Luis, Antipolo City since <u>{{ $field('resident_since') }}</u> and
                        has no derogatory record in our file this date.
                    @endif
                </p>
                @if ($indigency)
                    <p>This certification is being issued upon the request of the person mentioned above for the purpose
                        of:</p>
                    <div class="choices">
                        @foreach ([...$purposes, 'Others'] as $choice)
                            <div><b class="box"
                                    data-choice="{{ $choice }}">{{ !$draft && ($choice === 'Others' ? !in_array($purposeText, $purposes, true) : $purposeText === $choice) ? 'X' : '' }}</b>
                                {{ $choice }} @if ($choice === 'Others')
                                    (Specify)
                                    <u>{{ $draft ? '[[other_purpose]]' : (in_array($purposeText, $purposes, true) ? '________________' : $purposeText) }}</u>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <p>This document is issued this {{ $draft ? '____________, _____' : $issuedAt->format('F j, Y') }},
                        at Barangay San Luis, Antipolo City, Province of Rizal, by the Office of the Barangay upon the
                        request of the above-named individual, subject to the information and records available to this
                        office.</p>
                @else
                    <p class="purpose">This certification is being issued upon request for the purpose of
                        <u>{{ $purposeText }}</u>.</p>
                    <p>Issued this {{ $date }}, at Barangay San Luis, Antipolo City, Province of Rizal, by the
                        Office of the Barangay.</p>
                @endif
                <div class="attestation">Attested by:</div>
                <table class="officials">
                    <tr>
                        <td><b>JOCELYN P. CASTILLO</b><br>Barangay Secretary</td>
                        <td class="captain"><b>CRISOL L. CATE, SR.</b><br>Punong Barangay</td>
                    </tr>
                </table>
            @endif

            @if (!$indigency && !$business)
                @include('permits.verification-qr')
            @endif
            <table class="lower">
                <tr>
                    @if ($business)
                        <td class="records">FEE PAID: __________<br>O.R. NO.: __________<br>EXP DATE:
                            {{ $draft || !$document->expires_at ? '__________' : $document->expires_at->format('M j, Y') }}
                        </td>
                        <td>@include('permits.verification-qr')</td>
                        <td class="captain"><b>CRISOL L. CATE, SR.</b><br>Punong Barangay</td>
                    @else
                        @if ($indigency)
                            <td class="qr">@include('permits.verification-qr')</td>
                        @else
                            <td class="records">Community Tax No.: __________<br>Date Issued:
                                {{ $draft ? '_______________' : $issuedAt->format('F j, Y') }}<br>Issued at:
                                BarangayChain PH<br>Barangay OR. No.: __________</td>
                        @endif
                        <td></td>
                        <td class="client">
                            <div>Signature over Printed Name<br><b>Client</b></div>
                        </td>
                    @endif
                </tr>
            </table>
            @if (!$business && !$indigency && ($draft || $document->expires_at))
                <div class="note">
                    @if ($draft || $issuedAt->copy()->addMonthsNoOverflow(3)->isSameDay($document->expires_at))
                        NOTE: THIS CERTIFICATE IS VALID FOR THREE (3) MONTHS FROM DATE OF ISSUANCE.
                    @else
                        VALID UNTIL {{ strtoupper($document->expires_at->format('F j, Y')) }}.
                    @endif
                </div>
            @endif
        </main>
        <footer>BARANGAYCHAIN PH &middot; OFFICIAL DOCUMENT OF BARANGAY SAN LUIS</footer>
    </div>
</body>

</html>
