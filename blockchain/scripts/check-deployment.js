const { ethers } = require("ethers");

const ABI = [
    "function owner() view returns (address)",
    "function isIssuer(address account) view returns (bool)",
];

const rpcUrl = process.env.RPC_URL;
const configuredAddress = process.env.REGISTRY_ADDRESS;

function safeErrorMessage(error) {
    return String(error?.message ?? "unknown error")
        .replaceAll(rpcUrl ?? "", "[redacted RPC URL]")
        .replace(/(?:https?|wss?):\/\/\S+/gi, "[redacted URL]");
}

async function main() {
    if (!rpcUrl || !configuredAddress) {
        console.error(
            "FAIL: Set RPC_URL and REGISTRY_ADDRESS in the process environment."
        );
        process.exitCode = 1;
        return;
    }

    let provider;

    try {
        const address = ethers.getAddress(configuredAddress);
        provider = new ethers.JsonRpcProvider(rpcUrl);

        const network = await provider.getNetwork();
        const code = await provider.getCode(address);

        if (code === "0x") {
            throw new Error("No contract code exists at the configured address.");
        }

        const registry = new ethers.Contract(address, ABI, provider);
        const owner = await registry.owner();
        const ownerIsIssuer = await registry.isIssuer(owner);

        console.log("PASS: Contract code exists at the configured address.");
        console.log(`Contract: ${address}`);
        console.log(`Chain ID: ${network.chainId}`);
        console.log(`Owner: ${owner}`);

        if (ownerIsIssuer) {
            console.log("PASS: The owner is enabled as an issuer.");
        } else {
            console.error("FAIL: The owner is not enabled as an issuer.");
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
