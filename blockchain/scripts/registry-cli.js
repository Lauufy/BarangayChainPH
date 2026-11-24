import dotenv from "dotenv";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";
import { ethers } from "ethers";

if (process.env.REGISTRY_MANAGED_CONFIG !== "1") {
    dotenv.config({
        path: join(dirname(fileURLToPath(import.meta.url)), "..", ".env"),
        quiet: true,
    });
}

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
    "error NotDocumentIssuer()",
    "error NotPendingOwner()",
    "error ZeroAddress()",
    "error SameOwner()",
    "error ContractPaused()",
    "error EmptyHash()",
    "error ZeroDocumentId()",
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
    const expected = process.env.EXPECTED_CHAIN_ID || "11155111";

    if (network.chainId !== BigInt(expected)) {
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

function assertWritesAllowed(chainId) {
    if (chainId === 1n && process.env.ALLOW_MAINNET_WRITES !== "1") {
        throw new Error("Ethereum mainnet writes are disabled.");
    }
}

// Health check for the admin Smart Contract Management page.
// Prints the issuer's ADDRESS only, never the private key.
async function info() {
    const provider = new ethers.JsonRpcProvider(need("RPC_URL"));
    const address = need("REGISTRY_ADDRESS");
    const wallet = new ethers.Wallet(need("PRIVATE_KEY"));
    const network = await provider.getNetwork();
    const expected = process.env.EXPECTED_CHAIN_ID || "11155111";
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

    assertWritesAllowed(network.chainId);
    const wallet = new ethers.Wallet(need("PRIVATE_KEY"), provider);
    const writer = new ethers.Contract(address, ABI, wallet);
    const tx = await writer.setPaused(state);
    const receipt = await tx.wait(network.chainId === 1n ? 3 : 1);

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

    const network = await assertChain(provider, address);

    if (command === "find-issuance-tx") {

        const reader = new ethers.Contract(address, ABI, provider);
        const record = await reader.getRecord(documentId);
        if (!record.exists) {
            return { ok: true, found: false };
        }

        // The contract stores the issue block's timestamp. Find that block
        // without sending an unbounded eth_getLogs request to the RPC.
        let lower = 0;
        let upper = await provider.getBlockNumber();
        const issuedAt = Number(record.issuedAt);
        while (lower < upper) {
            const middle = Math.floor((lower + upper) / 2);
            const block = await provider.getBlock(middle);
            if (!block) {
                throw new Error("Could not read a block while recovering the issuance transaction.");
            }
            if (block.timestamp < issuedAt) {
                lower = middle + 1;
            } else {
                upper = middle;
            }
        }

        const events = await reader.queryFilter(
            reader.filters.DocumentIssued(documentId),
            lower,
            lower
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

    assertWritesAllowed(network.chainId);
    const wallet = new ethers.Wallet(need("PRIVATE_KEY"), provider);
    const writer = new ethers.Contract(address, ABI, wallet);

    const tx =
        command === "issue"
            ? await writer.issue(documentId, toFileHash(hash))
            : await writer.revoke(documentId);
    const receipt = await tx.wait(network.chainId === 1n ? 3 : 1);

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
                    error.revert?.name || (error.code ? "RPC request failed (" + error.code + ")" : error.message),
            },
            1
        )
    );
