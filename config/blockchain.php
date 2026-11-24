<?php

$network = env('BLOCKCHAIN_NETWORK', 'sepolia');

$networks = [
    'sepolia' => [
        'chain_id' => 11155111,
        'explorer_url' => env('BLOCKCHAIN_SEPOLIA_EXPLORER_URL', env('BLOCKCHAIN_EXPLORER_URL', 'https://sepolia.etherscan.io')),
        'contract' => env('BLOCKCHAIN_SEPOLIA_CONTRACT', env('BLOCKCHAIN_CONTRACT')),
        'rpc_url' => env('BLOCKCHAIN_SEPOLIA_RPC_URL', env('BLOCKCHAIN_RPC_URL')),
        'issuer_key' => env('BLOCKCHAIN_SEPOLIA_ISSUER_KEY', env('BLOCKCHAIN_ISSUER_KEY')),
    ],
    'mainnet' => [
        'chain_id' => 1,
        'explorer_url' => env('BLOCKCHAIN_MAINNET_EXPLORER_URL', 'https://etherscan.io'),
        'contract' => env('BLOCKCHAIN_MAINNET_CONTRACT'),
        'rpc_url' => env('BLOCKCHAIN_MAINNET_RPC_URL'),
        'issuer_key' => env('BLOCKCHAIN_MAINNET_ISSUER_KEY'),
    ],
];

return [
    'node_path' => env('BLOCKCHAIN_NODE_PATH') ?: 'node',
    'folder' => base_path('blockchain'),
    'network' => $network,
    'networks' => $networks,
    'mainnet_writes_enabled' => env('BLOCKCHAIN_MAINNET_WRITES_ENABLED', false),
    ...($networks[$network === 'live' ? 'sepolia' : $network] ?? []),
];
