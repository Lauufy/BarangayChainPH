const { ethers } = require("ethers");

const ABI = [
    "function owner() view returns (address)",
    "function isIssuer(address account) view returns (bool)",
];

const rpcUrl = process.env.RPC_URL;
const configuredAddress = process.env.REGISTRY_ADDRESS;
const configuredIssuer = process.env.ISSUER_ADDRESS;

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
        const code = await provider.getCode(address);

        if (code === "0x") {
            throw new Error("No contract code exists at the configured address.");
        }

        const registry = new ethers.Contract(address, ABI, provider);
        const owner = await registry.owner();
        const issuerIsAuthorized = await registry.isIssuer(issuerAddress);

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
