@php
    $business = $type === 'business_clearance';
    $indigency = $type === 'certificate_of_indigency';
    $purposes = config('barangay_services.services.certificate-of-indigency.fields.0.options');
    $draft = !isset($document);
    $field = fn ($key) => $draft ? "[[{$key}]]" : ($details[$key] ?? '________');
    $date = $draft ? '_____ day of __________, _____' : $issuedAt->format('jS').' day of '.$issuedAt->format('F, Y');
    $title = $business ? 'BUSINESS CLEARANCE' : ($indigency ? 'CERTIFICATE OF INDIGENCY' : 'CERTIFICATION');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    @page { size: A4 portrait; margin: 0; }
    body { margin: 0; color: black; background: white; font: 12pt/1.45 Helvetica, Arial, sans-serif; }
    .banner { display: block; width: 100%; }
    main { margin: 43pt 12.5% 0; }
    main.business { margin: 31pt 9.8% 0; font-size: 11pt; }
    h1 { text-align: center; margin: 0 0 34pt; font: normal 22pt Times, serif; }
    .indigency h1 { font: bold 17pt Helvetica, Arial, sans-serif; }
    .business h1 { font: bold italic 25pt Helvetica, Arial, sans-serif; margin-bottom: 0; }
    p { text-align: justify; text-indent: 36pt; margin: 0 0 12pt; }
    .center, .checks, .business-field { text-align: center; }
    .checks { margin: 18pt 0; color: #b90000; font-weight: bold; }
    .box { font-family: DejaVu Sans, sans-serif; color: black; }
    .business-field { width: 63%; margin: 10pt auto; font-size: 10pt; }
    .line { border-bottom: .7pt solid black; font-size: 12pt; overflow-wrap: break-word; }
    .findings { margin-top: 22pt; }
    li { margin-bottom: 7pt; }
    .choices { margin: 12pt 0 12pt 16%; }
    .choices div { margin-bottom: 8pt; }
    .signatures { width: 100%; margin-top: 46pt; border-collapse: collapse; page-break-inside: avoid; font-size: 11pt; }
    td { vertical-align: top; width: 50%; }
    .small { font-size: 8pt; }
    .client { display: inline-block; border-top: .7pt solid black; margin-top: 24pt; padding: 2pt 9pt; text-align: center; font-size: 9pt; }
    .note { color: #b90000; text-align: center; font-size: 9pt; margin-top: 10pt; }
    footer { margin: 12pt 10% 8pt; font: 7pt/1.2 Helvetica, Arial, sans-serif; color: #333; page-break-inside: avoid; }
    footer img { width: 58pt; height: 58pt; float: right; }
</style>
</head>
<body>
<img class="banner" alt="Barangay San Luis, City of Antipolo, Province of Rizal" src="{{ $draft ? asset('img/branding/header_permit.png') : 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/branding/header_permit.png'))) }}">
<main class="{{ $business ? 'business' : ($indigency ? 'indigency' : '') }}">
    <h1>{{ $title }}</h1>
    @if ($business)
        <div class="center">This is to certify that the Business / Trade Activity stated below:</div>
        <div class="checks">
            @foreach (['New application' => 'New', 'Renewal' => 'Renewal'] as $choice => $label)
                <span class="box" data-choice="{{ $choice }}">{{ ($details['application_type'] ?? '') === $choice ? '☒' : '□' }}</span> {{ $label }} &nbsp;&nbsp;&nbsp;
            @endforeach
        </div>
        <div class="business-field"><div class="line">{{ $field('business_name') }}</div>(Business Name / Trade Activity)</div>
        <div class="business-field"><div class="line">{{ $field('business_address') }}</div>Barangay San Luis, Antipolo City, Rizal<br>(Business Address)</div>
        <div class="business-field"><div class="line">{{ $field('business_owner_name') }}</div>(Owner/Manager/Operator)</div>
        <div class="business-field line">{{ $field('nature_of_business') }}</div>
        <div class="findings">is applying for a Barangay Business Clearance to secure corresponding Mayor’s Permit and has been found to be:</div>
        <ul>
            <li>In conformity with the provisions of existing Barangay Ordinance, Rules and Regulations;</li>
            <li>Not among those Businesses of Trade Activities banned in the Barangay;</li>
        </ul>
        <p>Issued this {{ $date }} at Barangay San Luis, Antipolo City, Province of Rizal, for whatever legal purpose it may serve.</p>
    @else
        <p>This is to certify that <u>{{ $name }}</u>, <u>{{ $age }}</u> years of age, {{ $nationality }}, and presently residing at <u>{{ $address }}</u>,
            @if ($indigency)
                is an indigent with no known source of income or livelihood.
            @else
                has lived in Barangay San Luis, Antipolo City since <u>{{ $field('resident_since') }}</u> and has no derogatory record in our file this date.
            @endif
        </p>
        @if ($indigency)
            <p>This certification is being issued upon the request of the person mentioned above for the purpose of:</p>
            <div class="choices">
                @foreach ([...$purposes, 'Others'] as $choice)
                    <div><span data-choice="{{ $choice }}">[ {{ (!$draft && ($choice === 'Others' ? !in_array($purposeText, $purposes, true) : $purposeText === $choice)) ? 'X' : ' ' }} ]</span>
                        {{ $choice }} @if ($choice === 'Others') (Specify) <u>{{ $draft ? '[[other_purpose]]' : (in_array($purposeText, $purposes, true) ? '________________' : $purposeText) }}</u> @endif
                    </div>
                @endforeach
            </div>
        @else
            <p>This certification is being issued upon request for the purpose of <u>{{ $purposeText }}</u>.</p>
        @endif
        <p>Issued this {{ $date }}, at Barangay Hall (Main/Annex) Building, Barangay San Luis, Antipolo City, Province of Rizal.</p>
    @endif
    <table class="signatures">
        <tr>
            <td>
                @if ($business)
                    <div class="small">FEE PAID: __________<br>O.R. NO.: __________<br>DATE: __________</div>
                @elseif ($indigency)
                    Attested by:<br><br><b>JOCELYN P. CASTILLO</b><br>Barangay Secretary
                @endif
            </td>
            <td><b>CRISOL L. CATE, SR.</b><br>Punong Barangay</td>
        </tr>
        @if (!$business)
            <tr>
                <td>@if (!$indigency) Attested by:<br><br><b>JOCELYN P. CASTILLO</b><br>Barangay Secretary<br><span class="client">Signature over Printed Name<br>Client</span>@endif</td>
                <td>
                    @if ($indigency)
                        <span class="client">Signature over Printed Name<br>Client</span>
                    @else
                        <br><br><div class="small">Community Tax No.: __________<br>Date issued: __________<br>Issued at: __________<br>Barangay OR No.: __________</div>
                    @endif
                </td>
            </tr>
        @endif
    </table>
    @if (!$business && !$indigency && ($draft || $document->expires_at))
        @if ($draft || $issuedAt->copy()->addMonthsNoOverflow(3)->isSameDay($document->expires_at))
            <div class="note">NOTE: THIS CERTIFICATE IS VALID FOR THREE (3) MONTHS FROM DATE OF ISSUANCE.</div>
        @else
            <div class="note">VALID UNTIL {{ strtoupper($document->expires_at->format('F j, Y')) }}.</div>
        @endif
    @endif
</main>
@if (!$draft)
    <footer>
        <img src="{{ $qrCode }}" alt="Verify this permit">
        @foreach (\App\Services\PermitFields::LABELS as $key => $label)
            <div>{{ $label }}: {{ $document->canonical_fields[$key] }}</div>
        @endforeach
        <div>Reference Number: {{ $application->reference_number }}</div>
    </footer>
@endif
</body>
</html>
