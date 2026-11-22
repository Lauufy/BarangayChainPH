<?php

return [
    // Any field whose name matches one of these is saved as [redacted] in the audit log.
    'redacted_fields' => [
        // Secrets and login data
        '*password*',
        '*remember_token*',
        '*token*',
        '*otp*',
        '*consent_ip*',
        '*verification_code*',
        '*two_factor*',
        '*2fa*',
        '*totp*',
        '*secret*',

        // ID numbers
        '*id_number*',
        '*id_number_hash*',
        '*identification_number*',
        '*identity_number*',
        '*national_id*',
        '*government_id*',
        '*passport_number*',
        '*license_number*',
        '*employee_id*',

        // Files and uploads
        '*url*',
        '*path*',
        '*file*',
        '*upload*',
        '*original_name*',
        '*content*',
        '*payload*',
        '*base64*',
        '*binary*',

        // Citizen personal data (exact names, so ip_address is NOT hidden)
        'address',
        'house_number',
        'street',
        'place_of_birth',
        'occupation',
        'religion',
        'date_of_birth',
        'civil_status',
        'phone',
    ],
];
