<?php

use App\Exports\AuditLogsExport;
use App\Exports\BlockchainLedgerExport;
use App\Exports\DocumentAuthenticationExport;
use App\Exports\IdentityVerificationsExport;
use App\Exports\PermitsExport;
use App\Exports\ReportsExport;
use App\Exports\TicketsExport;
use App\Exports\UsersExport;

return [
    'modules' => [
        'users' => [
            'export' => UsersExport::class,
            'permission' => null,
            'role' => 'admin',
            'filters' => ['users_tab'],
        ],
        'permits' => [
            'export' => PermitsExport::class,
            'permission' => 'permits.review',
            'filters' => [],
        ],
        'audit-logs' => [
            'export' => AuditLogsExport::class,
            'permission' => 'audit.view',
            'filters' => ['user_id', 'action', 'date'],
        ],
        'blockchain-ledger' => [
            'export' => BlockchainLedgerExport::class,
            'permission' => 'blockchain.view',
            'filters' => [],
        ],
        'identity-verifications' => [
            'export' => IdentityVerificationsExport::class,
            'permission' => 'identity.review',
            'filters' => [],
        ],
        'tickets' => [
            'export' => TicketsExport::class,
            'permission' => 'tickets.reply',
            'filters' => [],
        ],
        'document-authentication' => [
            'export' => DocumentAuthenticationExport::class,
            'permission' => 'documents.authenticate',
            'filters' => [],
        ],
        'reports' => [
            'export' => ReportsExport::class,
            'permission' => 'reports.view',
            'role' => 'admin',
            'filters' => ['from', 'to', 'permit_type', 'document_type', 'grouping'],
            'audit_action' => 'report_{format}_exported',
        ],
    ],
];
