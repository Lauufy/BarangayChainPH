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
|  fields           Application form fields. Types: text, number, tel, select, textarea.
|                   'required' => false makes a field optional.
|                   'wide' => true makes it full width.
|                   'other' => true (select only) adds an "Others" option with a text box.
|                   'prefill' => 'name' prefills from the citizen's name.
|                   'rules' => extra Laravel validation rules for the field.
|                   'when' => [field_key, value] shows the field only when that field has that value.
|                   'step' => number input step (e.g. '0.01').
|  purpose_template The purpose printed on the certificate. {field_key} is replaced
|                   with the citizen's answer.
|  requirements     Supporting document uploads (key => label).
|  requirement_rules Optional or conditional upload rules. Other uploads are required.
|
| Purposes that match another service (residency, indigency) are not listed under
| Barangay Clearance. Citizens request those as their own service.
|
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
                    'options' => ['Employment', 'Business', 'Travel / Abroad', 'Loan application', 'School requirement', 'ID application']],
                ['key' => 'contact_number', 'label' => 'Contact number', 'type' => 'tel', 'prefill' => 'phone'],
                ['key' => 'resident_since', 'label' => 'Resident since (year)', 'type' => 'number',
                    'rules' => ['integer', 'digits:4', 'min:1000', 'max:'.date('Y')]],
                ['key' => 'id_presented', 'label' => 'ID presented', 'type' => 'select', 'other' => true,
                    'options' => ['SSS', 'GSIS', 'TIN', 'Voter ID']],
                ['key' => 'vrn', 'label' => 'Voter registration number (VRN)', 'type' => 'text', 'required' => false],
            ],
            'requirements' => [
                'valid_id' => 'Valid government ID',
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
                    'options' => ['Scholarship', 'Medical Assistance', 'Financial Assistance']],
            ],
            'requirements' => [],
        ],

        'cedula' => [
            'type' => 'community_tax_certificate',
            'title' => 'Cedula / Resident Certificate',
            'description' => 'Request a community tax certificate, also called a resident certificate.',
            'icon' => 'M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1ZM8 9h8M8 13h8M8 17h5',
            'purpose_template' => 'Community tax certificate for {occupation}',
            'fields' => [
                ['key' => 'occupation', 'label' => 'Occupation', 'type' => 'text', 'prefill' => 'citizenProfile.occupation'],
                ['key' => 'tin', 'label' => 'TIN', 'type' => 'text', 'required' => false],
                ['key' => 'contact_number', 'label' => 'Contact number', 'type' => 'tel', 'prefill' => 'phone'],
                ['key' => 'annual_income', 'label' => 'Gross annual income (PHP)', 'type' => 'number',
                    'required' => false, 'step' => '0.01', 'rules' => ['numeric', 'min:0', 'decimal:0,2']],
            ],
            'requirements' => [
                'valid_id' => 'Valid government ID',
            ],
        ],

        'business-clearance' => [
            'type' => 'business_clearance',
            'title' => 'Barangay Business Clearance',
            'description' => 'Request a barangay clearance for a business operating in the barangay.',
            'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01',
            'purpose_template' => 'Business clearance for {business_name} ({application_type})',
            'fields' => [
                ['key' => 'application_type', 'label' => 'Application type', 'type' => 'select',
                    'options' => ['New application', 'Renewal']],
                ['key' => 'business_name', 'label' => 'Business name', 'type' => 'text'],
                ['key' => 'business_address', 'label' => 'Business address', 'type' => 'text', 'wide' => true],
                ['key' => 'business_owner_name', 'label' => 'Name of owner', 'type' => 'text', 'prefill' => 'name'],
                ['key' => 'business_type', 'label' => 'Type of ownership', 'type' => 'select',
                    'options' => ['Single proprietor', 'Corporation']],
                ['key' => 'nature_of_business', 'label' => 'Nature of business activity', 'type' => 'select', 'other' => true,
                    'options' => ['Manufacturer', 'Wholesaler', 'Retailer', 'Contractor', 'Bank/Financial institution', 'Restaurant/Carinderia', 'Service']],
                ['key' => 'business_contact_number', 'label' => 'Contact number', 'type' => 'tel', 'prefill' => 'phone'],
                ['key' => 'paid_up_capital', 'label' => 'Paid-up capital (PHP)', 'type' => 'number',
                    'when' => ['application_type', 'New application'], 'step' => '0.01', 'rules' => ['numeric', 'min:0', 'decimal:0,2']],
            ],
            'requirements' => [
                'business_registration' => 'DTI or SEC registration',
                'old_clearance' => 'Previous barangay business clearance',
                'health_permit' => 'Health / sanitation clearance or permit',
                'other' => 'Other supporting document',
            ],
            'requirement_rules' => [
                'old_clearance' => 'required_if:application_type,Renewal',
                'health_permit' => 'nullable',
                'other' => 'nullable',
            ],
        ],
    ],
];