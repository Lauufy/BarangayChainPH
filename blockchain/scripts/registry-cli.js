const path = require("path");
require("dotenv").config({
    path: path.join(__dirname, "..", ".env"),
    quiet: true,
});
const { ethers } = require("ethers");

const ABI = [
    "function issue(bytes32 documentId, bytes32 fileHash)",
    "function revoke(bytes32 documentId)",
    "function setPaused(bool state)",
    "function owner() view returns (address)",
    "function paused() view returns (bool)",
    "function isIssuer(address account) view returns (bool)",
    "function verify(bytes32 documentId, bytes32 fileHash) view returns (uint8)",
    "function getRecord(bytes32 documentId) view returns (bool exists, bytes32 fileHash, address issuer, uint64 issuedAt, uint64 revokedAt)",
    "event DocumentIssued(bytes32 indexed documentId, bytes32 fileHash, address indexed issuer, uint64 issuedAt)",
    "error NotOwner()",
    "error NotIssuer()",
    "error ContractPaused()",
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

// Safety: never read events from or write to a chain or address we did not expect
async function assertChain(provider, address) {
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

    return network;
}

// Health check for the admin Smart Contract Management page.
// Prints the issuer's ADDRESS only, never the private key.
async function info() {
    const provider = new ethers.JsonRpcProvider(need("RPC_URL"));
    const address = need("REGISTRY_ADDRESS");
    const wallet = new ethers.Wallet(need("PRIVATE_KEY"));
    const network = await provider.getNetwork();
    const expected = process.env.EXPECTED_CHAIN_ID;
    const [balance, code] = await Promise.all([
        provider.getBalance(wallet.address),
        provider.getCode(address),
    ]);
    const hasCode = code !== "0x";

    // A contract version without a function makes the call fail: treat that as "not supported"
    const attempt = async (fn) => {
        try {
            return await fn();
        } catch {
            return null;
        }
    };

    let owner = null;
    let paused = null;
    let issuerAuthorized = null;

    if (hasCode) {
        const reader = new ethers.Contract(address, ABI, provider);
        [owner, paused, issuerAuthorized] = await Promise.all([
            attempt(() => reader.owner()),
            attempt(() => reader.paused()),
            attempt(() => reader.isIssuer(wallet.address)),
        ]);
    }

    return {
        ok: true,
        contract: address,
        chainId: Number(network.chainId),
        chainMatches: expected
            ? Number(network.chainId) === Number(expected)
            : true,
        issuer: wallet.address,
        balance: ethers.formatEther(balance),
        hasCode,
        owner,
        isOwner: owner
            ? owner.toLowerCase() === wallet.address.toLowerCase()
            : null,
        issuerAuthorized,
        paused,
        supportsPause: paused !== null,
    };
}

// Owner-only emergency switch
async function setPaused(state) {
    const provider = new ethers.JsonRpcProvider(need("RPC_URL"));
    const address = need("REGISTRY_ADDRESS");
    const network = await assertChain(provider, address);

    const wallet = new ethers.Wallet(need("PRIVATE_KEY"), provider);
    const writer = new ethers.Contract(address, ABI, wallet);
    const tx = await writer.setPaused(state);
    const receipt = await tx.wait();

    return {
        ok: true,
        paused: state,
        txHash: receipt.hash,
        blockNumber: receipt.blockNumber,
        chainId: Number(network.chainId),
        contract: address,
    };
}

async function run([command, uuid, hash]) {
    if (command === "info") {
        return info();
    }

    if (command === "pause" || command === "unpause") {
        return setPaused(command === "pause");
    }

    if (
        !["issue", "revoke", "verify", "get", "find-issuance-tx"].includes(
            command
        ) ||
        !uuid
    ) {
        throw new Error(
            "Usage: registry-cli.js <issue|revoke|verify|get|find-issuance-tx> <document-uuid> [sha256-hex] | info | pause | unpause"
        );
    }

    const provider = new ethers.JsonRpcProvider(need("RPC_URL"));
    const address = need("REGISTRY_ADDRESS");
    const documentId = toDocumentId(uuid);

    if (command === "find-issuance-tx") {
        await assertChain(provider, address);

        const reader = new ethers.Contract(address, ABI, provider);
        const events = await reader.queryFilter(
            reader.filters.DocumentIssued(documentId),
            0,
            "latest"
        );
        const event = events.at(-1);

        return event
            ? {
                  ok: true,
                  found: true,
                  txHash: event.transactionHash,
                  blockNumber: event.blockNumber,
              }
            : { ok: true, found: false };
    }

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

    // issue / revoke
    const network = await assertChain(provider, address);

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
const limitMs = ["verify", "get", "info"].includes(args[0]) ? 10_000 : 90_000;
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