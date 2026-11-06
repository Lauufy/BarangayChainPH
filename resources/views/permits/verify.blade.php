@php
    $states = [
        'valid' => ['icon' => '✓', 'label' => 'Valid', 'message' => 'This certificate is valid.', 'style' => 'state-valid'],
        'not_found' => ['icon' => '?', 'label' => 'Not found', 'message' => 'No matching certificate record was found.', 'style' => 'state-neutral'],
        'invalid' => ['icon' => '!', 'label' => 'Invalid', 'message' => 'This certificate could not be verified.', 'style' => 'state-invalid'],
        'revoked' => ['icon' => '×', 'label' => 'Revoked', 'message' => 'This certificate has been revoked.', 'style' => 'state-invalid'],
        'unavailable' => ['icon' => '…', 'label' => 'Unavailable', 'message' => 'Verification could not be completed right now.', 'style' => 'state-warning'],
        // TODO: Add an 'expired' state when valid_until is available.
        // TODO: Add a 'mismatch' state when blockchain verification is implemented.
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
        .result { display: flex; align-items: flex-start; gap: 14px; padding: 20px; }
        .status-icon {
            display: grid;
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            place-items: center;
            border: 2px solid currentColor;
            border-radius: 50%;
            font-family: Arial, sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }
        .result h1 { margin: 0 0 6px; font-size: 1.5rem; line-height: 1.25; }
        .result p { margin: 0; font-size: 1rem; }
        .state-valid .result { background: var(--ok-bg); color: var(--ok); }
        .state-invalid .result { background: var(--bad-bg); color: var(--bad); }
        .state-warning .result { background: var(--warn-bg); color: var(--warn); }
        .state-neutral .result { background: var(--neutral-bg); color: var(--neutral); }
        dl { margin: 0; padding: 4px 20px 12px; }
        .row { padding: 12px 0; border-bottom: 1px solid var(--line); }
        .row:last-child { border-bottom: 0; }
        dt { color: var(--muted); font-size: 0.85rem; }
        dd { margin: 2px 0 0; font-size: 1.05rem; word-break: break-word; }
        .note { margin: 16px 4px 0; color: var(--muted); font-size: 0.85rem; }
    </style>
</head>
<body class="{{ $state['style'] }}">
<main class="page">
    <p class="issuer">{{ $barangayName }} document verification</p>

    <div class="card">
        <div class="result" role="status">
            <span class="status-icon" aria-hidden="true">{{ $state['icon'] }}</span>
            <div>
                <h1>{{ $state['label'] }}</h1>
                <p>{{ $state['message'] }}</p>
            </div>
        </div>

        @if ($status === 'valid')
            <dl>
                <div class="row">
                    <dt>Certificate type</dt>
                    <dd>{{ $documentTitle }}</dd>
                </div>
                <div class="row">
                    <dt>Holder</dt>
                    <dd>{{ $maskedApplicantName }}</dd>
                </div>
                <div class="row">
                    <dt>Reference number</dt>
                    <dd>{{ $referenceNumber }}</dd>
                </div>
                <div class="row">
                    <dt>Issue date</dt>
                    <dd>{{ $issuedAt?->format('F j, Y') }}</dd>
                </div>
                <div class="row">
                    <dt>Barangay</dt>
                    <dd>{{ $barangayName }}</dd>
                </div>
                {{-- TODO: Show valid_until here once the column is added and passed to this view. --}}
                @isset($validUntil)
                    <div class="row">
                        <dt>Valid until</dt>
                        <dd>{{ $validUntil?->format('F j, Y') }}</dd>
                    </div>
                @endisset
            </dl>
        @elseif ($status === 'revoked')
            <dl>
                <div class="row">
                    <dt>Certificate type</dt>
                    <dd>{{ $documentTitle }}</dd>
                </div>
                <div class="row">
                    <dt>Revoked on</dt>
                    <dd>{{ $revokedAt?->format('F j, Y') }}</dd>
                </div>
            </dl>
        @endif
    </div>

    @if ($status === 'valid')
        <p class="note">The holder's name is masked to protect their privacy.</p>
    @endif
</main>
</body>
</html>