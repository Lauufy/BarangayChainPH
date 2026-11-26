import { readFileSync } from "node:fs";
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
    "function issueVersioned(bytes32 documentId, bytes32 fileHash, uint32 version, bytes32 fieldsHash)",
    "function getVersionedRecord(bytes32 documentId) view returns (bool exists, bytes32 fileHash, uint64 revokedAt, uint32 version, bytes32 fieldsHash)",
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
    const configured = process.env.REGISTRY_ADDRESS;
    const address = configured && ethers.isAddress(configured) ? ethers.getAddress(configured) : null;
    let wallet = null;
    try {
        wallet = new ethers.Wallet(need("PRIVATE_KEY"));
    } catch {
        // Keep read-only monitoring available when the signer is missing.
    }
    const network = await provider.getNetwork();
    const expected = process.env.EXPECTED_CHAIN_ID || "11155111";
    const [balance, code] = await Promise.all([
        wallet ? provider.getBalance(wallet.address) : null,
        address ? provider.getCode(address) : "0x",
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

    let compatible = false;
    let owner = null;
    let paused = null;
    let issuerAuthorized = null;

    if (hasCode) {
        const reader = new ethers.Contract(address, ABI, provider);
        const probe = await attempt(() => reader.getVersionedRecord(ethers.ZeroHash));
        compatible = probe?.exists === false;
        [owner, paused, issuerAuthorized] = await Promise.all([
            attempt(() => reader.owner()),
            attempt(() => reader.paused()),
            wallet ? attempt(() => reader.isIssuer(wallet.address)) : null,
        ]);
    }

    return {
        ok: true,
        contract: address,
        chainId: Number(network.chainId),
        chainMatches: expected
            ? Number(network.chainId) === Number(expected)
            : true,
        issuer: wallet?.address ?? null,
        balance: balance === null ? null : ethers.formatEther(balance),
        hasCode,
        owner,
        isOwner: owner && wallet
            ? owner.toLowerCase() === wallet.address.toLowerCase()
            : null,
        issuerAuthorized,
        paused,
        compatible,
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
    if ((await writer.owner()) !== wallet.address || (await writer.paused()) === state) {
        throw new Error("Owner or recording state changed.");
    }
    if (process.env.REGISTRY_MANAGED_CONFIG === "1") {
        const request = await writer.setPaused.populateTransaction(state);
        const signed = await wallet.signTransaction(await wallet.populateTransaction(request));
        return {ok: true, signed, txHash: ethers.keccak256(signed)};
    }
    const tx = await writer.setPaused(state);
    const receipt = await tx.wait(network.chainId === 1n ? 3 : 1);

    return {
        ok: true,
        paused: await writer.paused({ blockTag: receipt.blockNumber }),
        txHash: receipt.hash,
        blockNumber: receipt.blockNumber,
        chainId: Number(network.chainId),
        contract: address,
    };
}

async function run([command, uuid, hash, version, fieldsHash]) {
    if (command === "deployment") {
        const provider = new ethers.JsonRpcProvider(need("RPC_URL"));
        const address = ethers.getAddress(need("REGISTRY_ADDRESS"));
        await assertChain(provider, address);
        const snapshot = await info();
        const receipt = await provider.getTransactionReceipt(uuid);
        const artifact = JSON.parse(readFileSync(new URL("../artifacts/contracts/PermitRegistry.sol/PermitRegistry.json", import.meta.url)));
        if (!receipt || receipt.status !== 1 || receipt.to !== null || receipt.contractAddress !== address ||
            await receipt.confirmations() < (snapshot.chainId === 1 ? 3 : 1) ||
            (await provider.getCode(address)).toLowerCase() !== artifact.deployedBytecode.toLowerCase() ||
            !snapshot.compatible || !snapshot.supportsPause || !snapshot.issuerAuthorized ||
            snapshot.owner !== ethers.getAddress(hash)) {
            throw new Error("Deployment evidence, registry interface, issuer authorization or owner did not match.");
        }
        return { ...snapshot, deployedAt: (await provider.getBlock(receipt.blockNumber)).timestamp };
    }
    if (command === "info") {
        return info();
    }

    if (command === "pause" || command === "unpause") {
        return setPaused(command === "pause");
    }

    if (
        !["prepare-revoke", "submit-revocation", "prepare-issue", "prepare-versioned", "submit-signed", "issue", "issue-versioned", "revoke", "verify", "get", "get-versioned", "find-issuance-tx"].includes(
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

        if (network.chainId === 1n && Number(status) === 1) {
            const blockTag = Math.max(0, await provider.getBlockNumber() - 2);
            if (Number(await reader.verify(documentId, toFileHash(hash), {blockTag})) !== 1) {
                throw new Error("Issuance is awaiting confirmations.");
            }
        }
        return { ok: true, status: STATUS[Number(status)] };
    }

    if (command === "get-versioned") {
        const reader = new ethers.Contract(address, ABI, provider);
        try {
            const record = await reader.getVersionedRecord(documentId);
            if (network.chainId === 1n && record.exists && record.revokedAt === 0n) {
                const blockTag = Math.max(0, await provider.getBlockNumber() - 2);
                if (!(await reader.getVersionedRecord(documentId, {blockTag})).exists) {
                    throw new Error("Issuance is awaiting confirmations.");
                }
            }
            return {
                ok: true,
                exists: record.exists,
                fileHash: record.fileHash,
                revokedAt: Number(record.revokedAt),
                version: Number(record.version),
                fieldsHash: record.fieldsHash,
            };
        } catch (error) {
            // Old deployments revert without data on an unknown selector.
            if (error.code !== "CALL_EXCEPTION" || error.data !== "0x") {
                throw error;
            }
            const record = await reader.getRecord(documentId);
            if (hash !== "allow-legacy") {
                return {
                    ok: false,
                    code: "legacy_registry",
                    exists: record.exists,
                    error: "This registry does not support versioned permits. Configure the upgraded PermitRegistry.",
                };
            }
            return {
                ok: true,
                exists: record.exists,
                fileHash: record.fileHash,
                revokedAt: Number(record.revokedAt),
                version: 0,
            };
        }
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
    if (command === "prepare-versioned" || command === "prepare-issue") {
        if ((await writer.owner()).toLowerCase() !== need("REGISTRY_OWNER").toLowerCase() ||
            !(await writer.isIssuer(wallet.address))) throw new Error("Issuer or owner validation failed.");
        if (await writer.paused()) throw new Error("ContractPaused");
        await writer.getVersionedRecord(ethers.ZeroHash);
    }

    if (command === "submit-signed" || command === "submit-revocation") {
        const signed = readFileSync(0, "utf8");
        const parsed = ethers.Transaction.from(signed);
        if (parsed.chainId !== network.chainId || parsed.to !== ethers.getAddress(address) ||
            parsed.from !== wallet.address || parsed.hash !== uuid) {
            throw new Error("Saved transaction does not match the configured signer or target.");
        }
        const method = writer.interface.parseTransaction({ data: parsed.data })?.name;
        const allowed = command === "submit-revocation" ? ["revoke"] : ["issue", "issueVersioned", "setPaused"];
        if (!allowed.includes(method) || parsed.value !== 0n) throw new Error("Unexpected signed operation.");
        let receipt = await provider.getTransactionReceipt(uuid);
        if (!receipt && !await provider.getTransaction(uuid)) {
            const stableBlock = Math.max(0, await provider.getBlockNumber() - (network.chainId === 1n ? 2 : 0));
            if (await provider.getTransactionCount(wallet.address, stableBlock) > parsed.nonce) {
                return {ok: false, replaced: true, txHash: uuid, error: "Signed transaction nonce already consumed."};
            }
            await provider.broadcastTransaction(signed);
        }
        receipt = await provider.waitForTransaction(uuid, network.chainId === 1n ? 3 : 1, 60000);
        if (!receipt) throw new Error("Transaction awaiting confirmation.");
        return { ok: receipt.status === 1, reverted: receipt.status === 0, txHash: uuid,
            error: receipt.status === 0 ? "Transaction reverted." : null, contract: address,
            chainId: Number(network.chainId), paused: method === "setPaused" ? await writer.paused({blockTag: receipt.blockNumber}) : undefined };
    }
    if (command === "prepare-revoke") {
        const request = await writer.revoke.populateTransaction(documentId);
        const signed = await wallet.signTransaction(await wallet.populateTransaction(request));
        return { ok: true, signed, txHash: ethers.keccak256(signed) };
    }
    if (command === "prepare-issue" || command === "prepare-versioned") {
        const request = command === "prepare-versioned"
            ? await writer.issueVersioned.populateTransaction(documentId, toFileHash(hash), version, toFileHash(fieldsHash))
            : await writer.issue.populateTransaction(documentId, toFileHash(hash));
        const signed = await wallet.signTransaction(await wallet.populateTransaction(request));
        return { ok: true, signed, txHash: ethers.keccak256(signed) };
    }
    let tx;
    if (command === "issue-versioned") {
        tx = await writer.issueVersioned(documentId, toFileHash(hash), version, toFileHash(fieldsHash));
    } else if (command === "issue") {
        tx = await writer.issue(documentId, toFileHash(hash));
    } else {
        tx = await writer.revoke(documentId);
    }
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
const limitMs = ["verify", "get", "get-versioned", "info"].includes(args[0]) ? 10_000 : 90_000;
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
                    error.revert?.name || (error.code ? "RPC request failed (" + error.code + ")" :
                        /https?:\/\/|(?:0x)?[\da-f]{64}/i.test(String(error.message)) ?
                            "Registry request failed." : String(error.message).slice(0, 250)),
            },
            1
        )
    );
