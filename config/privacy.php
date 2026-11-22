<?php

return [
    'policy_version' => env('PRIVACY_POLICY_VERSION', '1.0'),

    // Days the barangay has to answer a citizen's request.
    'request_response_days' => 30,

    'retention' => [
        // Automatic cleanup is OFF until you set PRIVACY_RETENTION_ENABLED=true in .env.
        'enabled' => env('PRIVACY_RETENTION_ENABLED', false),

        // A citizen with no activity for this long is erased (3 years).
        'inactive_account_days' => 1095,

        // Rejected and cancelled applications are deleted after this (1 year).
        'rejected_application_days' => 365,

        // ID photo and selfie are deleted this long after approval (6 months).
        'identity_files_after_verification_days' => 180,

        // Applicant details on an issued permit are removed after this (5 years).
        'issued_permit_years' => 5,

    ],
];