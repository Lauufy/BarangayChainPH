# Blockchain deployment record

Public information only. This file contains **no keys and no secrets**.

Last updated: TODO

## Deployment evidence

Fill in each TODO only after checking the deployed contract and its transaction on Sepolia. Do not treat source configuration or an old deployment note as proof of the live deployment.

| Item | Value |
|---|---|
| Network | TODO — confirm the live deployment network (intended demo network: Sepolia) |
| Chain ID | TODO — confirm the live chain ID (Sepolia: `11155111`) |
| Contract address | TODO — verify the live `PermitRegistry` address |
| Deployment transaction hash + Sepolia Etherscan link | `PASTE-THE-DEPLOYMENT-TRANSACTION-HASH-HERE` — TODO; link: <https://sepolia.etherscan.io/tx/PASTE-THE-DEPLOYMENT-TRANSACTION-HASH-HERE> |
| Deployment block | TODO — confirm from the deployment transaction receipt |
| Deployer address | TODO — confirm from the deployment transaction |
| Verified-source link | TODO — add the verified contract source link on Sepolia Etherscan |
| Date | TODO — record the deployment date |

Contract source in this repository: `blockchain/contracts/PermitRegistry.sol` (Solidity 0.8.24).

## Key custody and governance

- The current documented demo setup uses the same wallet as owner and issuer. The contract constructor also makes the deploying wallet the `owner` and enables it as an issuer; confirm the live values with the read-only checker below.
- The owner can add or remove issuer addresses with `setIssuer`. Issuers can issue and revoke documents. Store the issuer key only in an approved secrets manager or protected runtime secret store; never put it in Git, source code, screenshots, or client-side code.
- The contract has no ownership-transfer function. The owner cannot be rotated after deployment. Issuer access can be rotated only while the owner key remains available: enable a replacement issuer with `setIssuer`, verify it, then disable the previous issuer.
- If an issuer key is lost or compromised, stop issuing immediately, revoke affected documents where a trusted authorized issuer can still do so, and redeploy the registry with a new owner key. Re-anchor active documents in the new registry and update the application configuration. A revoked document cannot be un-revoked or issued again under the same document ID in this contract.
- If the owner key is lost or compromised, stop issuance, revoke affected documents where possible, redeploy and re-anchor; the contract does not support transferring ownership to recover the existing deployment.

## Read-only deployment check

Set `RPC_URL` and `REGISTRY_ADDRESS` in the process environment, then run `npm run check-deployment` from the `blockchain` folder. The checker reads the configured address, verifies contract code, and reads the chain ID, owner, and owner's issuer status. It never sends a transaction and redacts RPC URLs from errors.

## Live end-to-end demo checklist

Run this checklist manually against the verified deployment and capture screenshots as evidence:

- [ ] Approve a service request in the admin portal.
- [ ] Confirm the queue worker processed the blockchain recording job.
- [ ] Open the recorded transaction on Sepolia Etherscan and capture its successful status.
- [ ] Open the issued document's QR verification page and capture the valid result.
- [ ] Revoke the document in the admin portal and confirm the revocation job ran.
- [ ] Reload the QR verification page and capture the revoked result.

## What is stored on the blockchain

For each issued permit, the contract stores an opaque document ID (a hash of the document's UUID), the PDF's SHA-256 hash, the issuer address, the issue time, and the revocation time. **No names, addresses or other personal data are on the blockchain.**

## How anyone can check this

Once the TODO deployment evidence is filled and verified, you do not need an account on the explorer to view public addresses or transactions.

1. **See the transactions.** Open the verified contract link from the checklist above. Each permit issued appears as a transaction to this contract. Open one: the status should be **Success**, **To** should be the contract address, and **From** should be an authorized issuer.
2. **Start from the ledger.** The admin Blockchain Ledger page lists every recorded permit with a link to its transaction on the explorer.
3. **Compare a PDF with the blockchain.** Take a permit's document UUID (the last part of its QR link, `/verify/permit/<uuid>`) and, from the `blockchain` folder, run:

   ```
   node scripts/registry-cli.js get <document-uuid>
   ```

   It prints what the blockchain itself holds, including `fileHash`. Then calculate the PDF's hash:

   ```
   Get-FileHash .\permit.pdf -Algorithm SHA256
   ```

   If the two hashes are the same (ignoring capital letters and the `0x` prefix), the blockchain holds the fingerprint of exactly that PDF. The configured `REGISTRY_ADDRESS` and `RPC_URL` must point to the same deployment used by the live site.
4. **Tamper check.** Change one byte of a stored PDF: the public QR page shows **Invalid**, because the file's hash no longer matches.

## What this does not prove

This is a demo intended for a **test network**. Until the evidence checklist and live demo are completed, this document does not prove which contract is currently deployed. A test network does not show production readiness: there are no real citizens or real funds, the constructor initially assigns both owner and issuer to the deployer, and the contract has not been independently reviewed.
