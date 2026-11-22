<?php

return [
    // Full path to node.exe (from your Laravel .env)
    'node_path' => env('BLOCKCHAIN_NODE_PATH', 'node'),

    // The folder that contains scripts/registry-cli.js
    'folder' => base_path('blockchain'),

    // Which blockchain we use. Defaults are the current Sepolia test network.
    'network' => env('BLOCKCHAIN_NETWORK', 'sepolia'),
    'chain_id' => (int) env('BLOCKCHAIN_CHAIN_ID', 11155111),

    // Base address of the website that shows transactions (used to build links)
    'explorer_url' => env('BLOCKCHAIN_EXPLORER_URL', 'https://sepolia.etherscan.io'),

    // Filled in later, when we deploy to the real network
    'contract' => env('BLOCKCHAIN_CONTRACT'),
    'rpc_url' => env('BLOCKCHAIN_RPC_URL'),
    'issuer_key' => env('BLOCKCHAIN_ISSUER_KEY'),
];