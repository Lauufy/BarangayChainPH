@php
    $states = [
        'valid' => [
            'headline' => 'This document is genuine',
            'detail' => 'It was issued by '.$barangayName.' and the file on record is unchanged.',
        ],
        'revoked' => [
            'headline' => 'This document has been revoked',
            'detail' => 'It is no longer valid. Contact the barangay hall for details.',
        ],
        'invalid' => [
            'headline' => 'This document cannot be confirmed',
            'detail' => 'The record does not match the file on record. Do not accept this copy. Ask the holder to visit the barangay hall.',
        ],
        'unavailable' => [
            'headline' => 'We could not check this document',
            'detail' => 'The record service did not respond. Scan the code again in a few minutes.',
        ],
        'not_found' => [
            'headline' => 'No record found',
            'detail' => 'This code does not match any document issued by '.$barangayName.'.',
        ],
    ];
    $state = $states[$status] ?? $states['not_found'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Verify document · {{ $barangayName }}</title>
    <style>
        :root {
            --bg: #f6f5f1;
            --surface: #ffffff;
            --text: #1d2321;
            --muted: #5d6662;
            --line: #d9dcd6;
            --ok: #1d6b45;
            --ok-bg: #e4f2ea;
            --bad: #a6322a;
            --bad-bg: #f8e6e4;
            --warn: #8a5a00;
            --warn-bg: #fbf0d9;
            --neutral: #4a5350;
            --neutral-bg: #eceeea;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #141816;
                --surface: #1e2321;
                --text: #eef0ed;
                --muted: #a3ada8;
                --line: #343b38;
                --ok: #7fd1a3;
                --ok-bg: #1a3328;
                --bad: #f0a099;
                --bad-bg: #3a1f1d;
                --warn: #e8c070;
                --warn-bg: #36290f;
                --neutral: #c0c8c4;
                --neutral-bg: #2a302d;
            }
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: Georgia, 'Times New Roman', serif;
            line-height: 1.5;
            padding: 24px 16px 48px;
        }
        .page { max-width: 480px; margin: 0 auto; }
        .issuer { margin: 0 0 20px; color: var(--muted); font-size: 0.95rem; }
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
        }
        .result { padding: 20px; }
        .result h1 { margin: 0 0 6px; font-size: 1.4rem; line-height: 1.25; }
        .result p { margin: 0; font-size: 1rem; }
        .valid .result { background: var(--ok-bg); color: var(--ok); }
        .revoked .result, .invalid .result { background: var(--bad-bg); color: var(--bad); }
        .unavailable .result { background: var(--warn-bg); color: var(--warn); }
        .not_found .result { background: var(--neutral-bg); color: var(--neutral); }
        dl { margin: 0; padding: 4px 20px 12px; }
        .row { padding: 12px 0; border-bottom: 1px solid var(--line); }
        .row:last-child { border-bottom: 0; }
        dt { color: var(--muted); font-size: 0.85rem; }
        dd { margin: 2px 0 0; font-size: 1.05rem; word-break: break-word; }
        .note { margin: 16px 4px 0; color: var(--muted); font-size: 0.85rem; }
    </style>
</head>
<body class="{{ $status }}">
<main class="page">
    <p class="issuer">{{ $barangayName }} document verification</p>

    <div class="card">
        <div class="result" role="status">
            <h1>{{ $state['headline'] }}</h1>
            <p>{{ $state['detail'] }}</p>
        </div>

        @if ($status === 'valid')
            <dl>
                <div class="row">
                    <dt>Document</dt>
                    <dd>{{ $documentTitle }}</dd>
                </div>
                <div class="row">
                    <dt>Issued to</dt>
                    <dd>{{ $maskedApplicantName }}</dd>
                </div>
                <div class="row">
                    <dt>Document number</dt>
                    <dd>{{ $permitNumber }}</dd>
                </div>
                <div class="row">
                    <dt>Reference number</dt>
                    <dd>{{ $referenceNumber }}</dd>
                </div>
                <div class="row">
                    <dt>Date issued</dt>
                    <dd>{{ $issuedAt?->format('F j, Y') }}</dd>
                </div>
                {{-- Phase 1B: add a "Valid until" row from valid_until. --}}
            </dl>
        @elseif ($status === 'revoked')
            <dl>
                <div class="row">
                    <dt>Document</dt>
                    <dd>{{ $documentTitle }}</dd>
                </div>
                <div class="row">
                    <dt>Revoked on</dt>
                    <dd>{{ $revokedAt?->format('F j, Y') }}</dd>
                </div>
            </dl>
        @endif
    </div>

    <p class="note">The holder's name is shortened on purpose to protect their privacy.</p>
</main>
</body>
</html>