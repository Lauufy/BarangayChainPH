<?php

return [
    // Every permission an admin can give to a staff member
    'list' => [
        'identity.review' => 'Identity Verification',
        'permits.review' => 'Permit Management (view, approve, reject)',
        'permits.print-limit' => 'Change permit print limit',
        'tickets.reply' => 'Ticket Messages',
        'documents.authenticate' => 'Document Authentication',
        'blockchain.view' => 'Blockchain Ledger',
        'reports.view' => 'Reports & Analytics',
        'audit.view' => 'Audit Logs',
    ],

    // Given to new staff accounts
    'staff_defaults' => [
        'identity.review',
        'permits.review',
        'tickets.reply',
        'documents.authenticate',
    ],
];