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

- The constructor makes the deploying wallet the `owner` and initially enables it as an issuer. The current source supports a two-step ownership transfer: the owner calls `transferOwnership`, then the new owner calls `acceptOwnership`. Acceptance removes the previous owner's issuer permission. For production, transfer ownership to a multisig and separately authorize the backend issuer with `setIssuer`.
- Issuers can issue documents and revoke only documents they issued. The owner can revoke any document for emergency recovery. Store the issuer key only in an approved secrets manager or protected runtime secret store; never put it in Git, source code, screenshots, or client-side code.
- These protections are in the current source, not retroactively in an already deployed contract. Existing deployments are immutable; deploy and verify this source as a new version before relying on ownership transfer or issuer-scoped revocation.
- If an issuer key is lost or compromised, pause issuance, remove the issuer with `setIssuer`, and have the owner revoke affected records where needed. A revoked document cannot be un-revoked or issued again under the same document ID in this contract.
- If the owner key is lost before transferring ownership to a multisig, recovery still requires deploying a new registry and re-anchoring active documents. Once a multisig owns the registry, apply its documented signer-recovery process.

## Synchronize the application registry

Use a maintainer shell with access to the application and database. The web dashboard reads deployment history. The configured network and contract select the issuance target, and a validated deployment row must match before the application can sign a new issuance.

1. Apply the Laravel migrations. Keep the existing deployment and permit rows.
2. Supply the selected network's RPC endpoint, registry address and issuer key through the application's secret configuration. Refresh cached configuration and restart long-running workers after changing these settings.
3. Make the Hardhat artifact from the reviewed deployment build available at `blockchain/artifacts/contracts/PermitRegistry.sol/PermitRegistry.json`. Runtime bytecode must match exactly, including compiler metadata.
4. Obtain the contract creation transaction and independently confirm the expected current owner. Run the command below with those public values. The version is a maintainer-supplied release label. Synchronization reads the chain and updates local deployment history.

```sh
php artisan blockchain:sync-deployment <creation-transaction-hash> <release-version> <expected-owner-address>
```

Use a shared atomic-lock cache and an asynchronous queue in production. Keep the application encryption key available to workers. Interrupted submissions reuse the encrypted signed transaction. A low balance, a missing artifact, an incompatible historical registry, or a configuration mismatch requires maintainer attention. Drain outstanding transactions before changing the network, registry, or signer. Historical verification uses the original anchor. An independently controlled owner can authorize an operational issuer with the existing Solidity interface. Dashboard pause and resume require the configured signer to own the registry. For multisig ownership, use the owner's approved transaction process, refresh status and retry eligible held permits.

## Read-only deployment check

Set `RPC_URL`, `REGISTRY_ADDRESS`, and `ISSUER_ADDRESS` in the process environment, then run `npm run check-deployment` from the `blockchain` folder. The checker reads the configured address, verifies contract code, and reads the chain ID, owner, and configured issuer's authorization. It never sends a transaction and redacts RPC URLs from errors.

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

## Cutover from Sepolia to Ethereum Mainnet

Keep the application on `BLOCKCHAIN_NETWORK=sepolia` with
`BLOCKCHAIN_MAINNET_WRITES_ENABLED=false` while preparing the deployment.
Mainnet is chain ID 1 and Sepolia is chain ID 11155111.

1. Inventory pending and recorded blockchain rows. Confirm that legacy `live`
   and blank network values refer to Sepolia. Check existing contracts against
   the corresponding transaction receipts. For older rows with a blank contract,
   the app uses the configured Sepolia registry. Backfill any exceptions from
   verified receipts before switching, and keep the historical RPC and relevant
   issuer credentials available for verification and revocation.
2. Rehearse issuance, verification, recovery after a worker interruption, and
   revocation on Sepolia. Review the Solidity contract and the decision to
   publish permit fingerprints on Mainnet before anchoring production permits.
3. From the `blockchain` directory, load `MAINNET_RPC_URL` and
   `MAINNET_DEPLOYER_KEY` from the deployment secret store. For the approved
   deployment only, set `ALLOW_MAINNET_DEPLOY=1` in the process environment and
   run `npx hardhat run scripts/deploy.js --network mainnet`. Record the address,
   receipt, block, and verified source. Clear the deployment opt-in afterward.
4. Transfer ownership to the production multisig and accept the transfer from
   the multisig. Authorize and fund a separate backend issuer. Manage pause and
   unpause through the multisig. Run `npm run check-deployment` with
   `EXPECTED_CHAIN_ID=1`, `EXPECTED_OWNER` set to the multisig,
   and `RPC_URL`, `REGISTRY_ADDRESS`, and `ISSUER_ADDRESS` set to the
   production values. This check sends no transaction. Verify the multisig
   signer set and threshold independently.
5. Configure `BLOCKCHAIN_MAINNET_CONTRACT`, `BLOCKCHAIN_MAINNET_RPC_URL`, and
   `BLOCKCHAIN_MAINNET_ISSUER_KEY` in the application. Keep the Sepolia
   settings. Pause permit approvals and drain the blockchain queue during the
   switch. Set `BLOCKCHAIN_NETWORK=mainnet` and
   `BLOCKCHAIN_MAINNET_WRITES_ENABLED=true`, refresh cached config, and restart
   the web and queue processes together. Use one writing worker per signer and
   a queue visibility or retry timeout greater than the 120-second job timeout.
6. Synchronize the configured Mainnet registry using the maintainer command above.
   With explicit approval, issue a controlled permit and confirm its Mainnet receipt, QR result, ledger link, and revocation. Check an existing Sepolia QR and ledger link. Monitor
   failed jobs, RPC errors, and issuer balance before reopening approvals.

Each permit keeps its original network and registry. Switching the default
changes where new permits are anchored. Existing Sepolia transactions stay on
Sepolia. If a permit needs a Mainnet anchor, use the permit reissue workflow
after the switch to create a new version, then verify both the new anchor and
the old permit's revocation.

To roll back new issuance, pause approvals, drain or reconcile jobs, switch the
default back to Sepolia, refresh cached config, and restart workers. Retain both
network profiles. Pending jobs keep their original target, and Mainnet permits
continue to use Mainnet. Turning off Mainnet writes also suspends pending
Mainnet issuance and on-chain revocations until writes are re-enabled.
