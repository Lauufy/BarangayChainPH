// Small bridge between Laravel and the PermitRegistry contract.
//
// Usage (run from the blockchain folder):
//   node scripts/registry-cli.js issue  <document-uuid> <sha256-hex>
//   node scripts/registry-cli.js revoke <document-uuid>
//   node scripts/registry-cli.js verify <document-uuid> <sha256-hex>
//   node scripts/registry-cli.js get    <document-uuid>
//
// It prints exactly one line of JSON and exits with code 1 on failure.
// verify results: not_found, valid, revoked, mismatch.

const path = require("path");
require("dotenv").config({
    path: path.join(__dirname, "..", ".env"),
    quiet: true,
});
const { ethers } = require("ethers");

const ABI = [
    "function issue(bytes32 documentId, bytes32 fileHash)",
    "function revoke(bytes32 documentId)",
    "function verify(bytes32 documentId, bytes32 fileHash) view returns (uint8)",
    "function getRecord(bytes32 documentId) view returns (bool exists, bytes32 fileHash, address issuer, uint64 issuedAt, uint64 revokedAt)",
    "error NotOwner()",
    "error NotIssuer()",
    "error EmptyHash()",
    "error AlreadyIssued()",
    "error DocumentNotFound()",
    "error AlreadyRevoked()",
];

// Same order as the Status enum in PermitRegistry.sol
const STATUS = ["not_found", "valid", "revoked", "mismatch"];

const toDocumentId = (uuid) =>
    ethers.keccak256(ethers.toUtf8Bytes(String(uuid).trim().toLowerCase()));

function toFileHash(hex) {
    const clean = String(hex ?? "")
        .trim()
        .toLowerCase()
        .replace(/^0x/, "");

    if (!/^[0-9a-f]{64}$/.test(clean)) {
        throw new Error("The file hash must be 64 hex characters (SHA-256).");
    }

    return `0x${clean}`;
}

function need(name) {
    if (!process.env[name]) {
        throw new Error(`${name} is missing in blockchain/.env`);
    }

    return process.env[name];
}

async function run([command, uuid, hash]) {
    if (!["issue", "revoke", "verify", "get"].includes(command) || !uuid) {
        throw new Error(
            "Usage: registry-cli.js <issue|revoke|verify|get> <document-uuid> [sha256-hex]"
        );
    }

    const provider = new ethers.JsonRpcProvider(need("RPC_URL"));
    const address = need("REGISTRY_ADDRESS");
    const documentId = toDocumentId(uuid);

    if (command === "verify") {
        const reader = new ethers.Contract(address, ABI, provider);
        const status = await reader.verify(documentId, toFileHash(hash));

        return { ok: true, status: STATUS[Number(status)] };
    }

    if (command === "get") {
        const reader = new ethers.Contract(address, ABI, provider);
        const record = await reader.getRecord(documentId);

        return {
            ok: true,
            exists: record.exists,
            fileHash: record.fileHash,
            issuer: record.issuer,
            issuedAt: Number(record.issuedAt),
            revokedAt: Number(record.revokedAt),
        };
    }

    // Safety: never write to a chain or address we did not expect
    const network = await provider.getNetwork();
    const expected = process.env.EXPECTED_CHAIN_ID;

    if (expected && Number(network.chainId) !== Number(expected)) {
        throw new Error(
            `Wrong network: expected chain ${expected} but connected to chain ${network.chainId}`
        );
    }

    if ((await provider.getCode(address)) === "0x") {
        throw new Error(
            "No contract found at the configured address on this network."
        );
    }

    const wallet = new ethers.Wallet(need("PRIVATE_KEY"), provider);
    const writer = new ethers.Contract(address, ABI, wallet);

    const tx =
        command === "issue"
            ? await writer.issue(documentId, toFileHash(hash))
            : await writer.revoke(documentId);
    const receipt = await tx.wait();

    return {
        ok: true,
        documentId,
        txHash: receipt.hash,
        blockNumber: receipt.blockNumber,
        chainId: Number(network.chainId),
        contract: address,
    };
}

const args = process.argv.slice(2);
// Reads should be fast. Writes wait for a block, which can take a while on a test network.
const limitMs = ["verify", "get"].includes(args[0]) ? 10_000 : 90_000;
const watchdog = setTimeout(
    () => finish({ ok: false, error: "timeout" }, 1),
    limitMs
);

function finish(result, code) {
    clearTimeout(watchdog);
    console.log(JSON.stringify(result));
    process.exit(code);
}

run(args)
    .then((result) => finish(result, 0))
    .catch((error) =>
        finish(
            {
                ok: false,
                error:
                    error.revert?.name || error.shortMessage || error.message,
            },
            1
        )
    );
