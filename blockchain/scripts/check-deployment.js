import { ethers } from "ethers";

const ABI = [
    "function paused() view returns (bool)",
    "function owner() view returns (address)",
    "function isIssuer(address account) view returns (bool)",
];

const rpcUrl = process.env.RPC_URL;
const configuredAddress = process.env.REGISTRY_ADDRESS;
const configuredIssuer = process.env.ISSUER_ADDRESS;
const expectedOwner = process.env.EXPECTED_OWNER;
const expectedChainId = BigInt(process.env.EXPECTED_CHAIN_ID || "11155111");

function safeErrorMessage(error) {
    return String(error?.message ?? "unknown error")
        .replaceAll(rpcUrl ?? "", "[redacted RPC URL]")
        .replace(/(?:https?|wss?):\/\/\S+/gi, "[redacted URL]");
}

async function main() {
    if (!rpcUrl || !configuredAddress || !configuredIssuer) {
        console.error(
            "FAIL: Set RPC_URL, REGISTRY_ADDRESS, and ISSUER_ADDRESS in the process environment."
        );
        process.exitCode = 1;
        return;
    }

    let provider;

    try {
        const address = ethers.getAddress(configuredAddress);
        const issuerAddress = ethers.getAddress(configuredIssuer);
        provider = new ethers.JsonRpcProvider(rpcUrl);

        const network = await provider.getNetwork();
        if (network.chainId !== expectedChainId) {
            throw new Error(`Expected chain ${expectedChainId}, connected to ${network.chainId}.`);
        }
        if (network.chainId === 1n && !expectedOwner) {
            throw new Error("Set EXPECTED_OWNER to the production multisig address.");
        }
        const code = await provider.getCode(address);

        if (code === "0x") {
            throw new Error("No contract code exists at the configured address.");
        }

        const registry = new ethers.Contract(address, ABI, provider);
        const owner = await registry.owner();
        const issuerIsAuthorized = await registry.isIssuer(issuerAddress);

        if (expectedOwner && owner !== ethers.getAddress(expectedOwner)) {
            throw new Error("The configured multisig does not own this registry.");
        }
        if (network.chainId === 1n) {
            const [ownerCode, paused, issuerBalance] = await Promise.all([
                provider.getCode(owner),
                registry.paused(),
                provider.getBalance(issuerAddress),
            ]);
            if (ownerCode === "0x" || owner === issuerAddress || paused || issuerBalance === 0n) {
                throw new Error("Mainnet requires a contract owner separate from the funded issuer and an unpaused registry.");
            }
        }

        console.log("PASS: Contract code exists at the configured address.");
        console.log(`Contract: ${address}`);
        console.log(`Chain ID: ${network.chainId}`);
        console.log(`Owner: ${owner}`);
        console.log(`Issuer: ${issuerAddress}`);

        if (issuerIsAuthorized) {
            console.log("PASS: The configured issuer is authorized.");
        } else {
            console.error("FAIL: The configured issuer is not authorized.");
            process.exitCode = 1;
        }
    } catch (error) {
        console.error(`FAIL: Deployment check could not complete: ${safeErrorMessage(error)}`);
        process.exitCode = 1;
    } finally {
        await provider?.destroy();
    }
}

main();
