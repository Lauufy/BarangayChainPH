<?php

return [
    'thresholds' => [
        // Backlog alerts include pending or processing requests older than this many days.
        'backlog_days' => 3,
        // High rejection rates are strict-greater-than percentages after reaching the minimum sample size.
        'rejection_rate_percent' => 30,
        'rejection_minimum_requests' => 10,
        'blockchain_pending_minutes' => 30,
    ],
];
