# Blockchain deployment record

Public information only. This file contains **no keys and no secrets**.

Last updated: November 12, 2026

## Deployment details

| Item | Value |
|---|---|
| Network | Ethereum **Sepolia** (a public test network) |
| Chain ID | `11155111` |
| Contract | `PermitRegistry` at `0xCC07Ab078C7486946D1EF29d51d18e5D48999A61` |
| Owner and issuer wallet | `0xC7dc19702AA48c0B2D000C33c4B343265B8FFF21` (the demo uses one wallet for both roles) |
| Deployment transaction | `PASTE-THE-DEPLOYMENT-TRANSACTION-HASH-HERE` |
| Contract source | `blockchain/contracts/PermitRegistry.sol` (Solidity 0.8.24) |
| Source verified on the explorer | Yes|

Explorer links:

- Contract: <https://sepolia.etherscan.io/address/0xCC07Ab078C7486946D1EF29d51d18e5D48999A61>
- Wallet: <https://sepolia.etherscan.io/address/0xC7dc19702AA48c0B2D000C33c4B343265B8FFF21>

## What is stored on the blockchain

For each issued permit, only two values are stored: an opaque document ID (a hash of the document's UUID) and the SHA-256 hash of the PDF. **No names, addresses or other personal data are on the blockchain.**

## How anyone can check this

You do not need an account on the explorer to view addresses or transactions.

1. **See the transactions.** Open the contract link above. Each permit issued appears as a transaction to this contract. Open one: the status should be **Success**, **To** should be the contract address, and **From** should be the issuer wallet.
2. **Start from the ledger.** The admin Blockchain Ledger page lists every recorded permit with a link to its transaction on the explorer.
3. **Compare a PDF with the blockchain.** Take a permit's document UUID (the last part of its QR link, `/verify/permit/<uuid>`) and, from the `blockchain` folder, run:

   ```
   node scripts/registry-cli.js get <document-uuid>
   ```

   It prints what the blockchain itself holds, including `fileHash`. Then calculate the PDF's hash:

   ```
   Get-FileHash .\permit.pdf -Algorithm SHA256
   ```

   If the two hashes are the same (ignoring capital letters and the `0x` prefix), the blockchain holds the fingerprint of exactly that PDF. For this to work, the local `blockchain/.env` must point to the same contract (`REGISTRY_ADDRESS`) as the live site.
4. **Tamper check.** Change one byte of a stored PDF: the public QR page shows **Invalid**, because the file's hash no longer matches.

## What this does not prove

This is a working demo on a **test network**. It does not show production readiness: there are no real citizens or real funds, one wallet holds both roles, and the contract has not been independently reviewed.
