<?php

/*
|--------------------------------------------------------------------------
| Barangay services
|--------------------------------------------------------------------------
| One entry per service. The array key is the URL slug.
|
|  type             Saved on the request. Must match a ServiceRequest constant.
|  title            Name shown to citizens, staff and on the PDF.
|  description      Short text on the services page.
|  icon             SVG path for the services page card.
|  fields           Step 2 form fields. Types: text, number, tel, select, textarea.
|                   'required' => false makes a field optional.
|                   'wide' => true makes it full width.
|                   'other' => true (select only) adds an "Others" option with a text box.
|                   'prefill' => 'name' prefills from the citizen's name.
|  purpose_template The purpose printed on the certificate. {field_key} is replaced
|                   with the citizen's answer.
|  requirements     Documents the citizen must upload on step 3 (key => label).
|                   Only list what is truly required. All listed files are required.
|
| Fields, purposes and requirements are PLACEHOLDERS. Replace them when the
| barangay confirms the real list. No other code needs to change.
*/

return [

    'services' => [

        'barangay-clearance' => [
            'type' => 'barangay_clearance',
            'title' => 'Barangay Clearance',
            'description' => 'Request a clearance for employment, business, or other official purposes.',
            'icon' => 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z',
            'purpose_template' => '{purpose}',
            'fields' => [
                ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'other' => true,
                    'options' => ['Employment', 'Business', 'Travel or abroad', 'Government transaction', 'School requirement']],
                ['key' => 'remarks', 'label' => 'Additional remarks', 'type' => 'textarea', 'required' => false, 'wide' => true],
            ],
            'requirements' => [
                'valid_id' => 'Valid government ID',
                'proof_of_residency' => 'Proof of residency (e.g., utility bill)',
                'cedula' => 'Community Tax Certificate (Cedula)',
            ],
        ],

        'certificate-of-residency' => [
            'type' => 'certificate_of_residency',
            'title' => 'Certificate of Residency',
            'description' => 'Request proof of residency within the barangay.',
            'icon' => 'M3 10.5 12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6',
            'purpose_template' => '{purpose}',
            'fields' => [
                ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'other' => true,
                    'options' => ['Employment', 'School enrollment', 'Government transaction', 'Bank or financial transaction']],
                ['key' => 'remarks', 'label' => 'Additional remarks', 'type' => 'textarea', 'required' => false, 'wide' => true],
            ],
            'requirements' => [
                'valid_id' => 'Valid government ID',
                'proof_of_residency' => 'Proof of residency (e.g., utility bill)',
            ],
        ],

        'certificate-of-indigency' => [
            'type' => 'certificate_of_indigency',
            'title' => 'Certificate of Indigency',
            'description' => 'Request a certificate for assistance programs. Barangay staff will check your eligibility.',
            'icon' => 'M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2ZM14 3v5h5M9 13h6M9 17h6',
            'purpose_template' => '{purpose}',
            'fields' => [
                ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'other' => true,
                    'options' => ['Medical assistance', 'Educational assistance', 'Burial assistance', 'Legal assistance']],
                ['key' => 'remarks', 'label' => 'Additional remarks', 'type' => 'textarea', 'required' => false, 'wide' => true],
            ],
            'requirements' => [
                'valid_id' => 'Valid government ID',
                'proof_of_income' => 'Proof of low or no income (e.g., certificate of unemployment)',
            ],
        ],

        'business-clearance' => [
            'type' => 'business_clearance',
            'title' => 'Barangay Business Clearance',
            'description' => 'Request a barangay clearance for a business operating in the barangay.',
            'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01',
            'purpose_template' => 'Business clearance for {business_name} ({application_type})',
            'fields' => [
                ['key' => 'applicant_role', 'label' => 'Applicant role', 'type' => 'select',
                    'options' => ['Owner', 'Authorized representative']],
                ['key' => 'business_name', 'label' => 'Business name', 'type' => 'text'],
                ['key' => 'business_type', 'label' => 'Business type', 'type' => 'select',
                    'options' => ['Sole proprietorship', 'Partnership', 'Corporation', 'Cooperative']],
                ['key' => 'nature_of_business', 'label' => 'Nature of business', 'type' => 'text'],
                ['key' => 'business_address', 'label' => 'Business address', 'type' => 'text', 'wide' => true],
                ['key' => 'business_contact_number', 'label' => 'Business contact number', 'type' => 'tel'],
                ['key' => 'business_owner_name', 'label' => 'Business owner name', 'type' => 'text', 'prefill' => 'name'],
                ['key' => 'number_of_employees', 'label' => 'Number of employees', 'type' => 'number', 'required' => false],
                ['key' => 'business_area', 'label' => 'Business area (square meters)', 'type' => 'number', 'required' => false],
                ['key' => 'application_type', 'label' => 'Application type', 'type' => 'select',
                    'options' => ['New application', 'Renewal']],
                ['key' => 'remarks', 'label' => 'Additional remarks', 'type' => 'textarea', 'required' => false, 'wide' => true],
            ],
            'requirements' => [
                'valid_id' => 'Valid government-issued ID',
                'cedula' => 'Community Tax Certificate (Cedula)',
                'business_registration' => 'Business registration certificate',
            ],
        ],

    ],

];