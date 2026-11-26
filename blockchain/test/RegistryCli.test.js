import { expect } from "chai";
import { network, artifacts } from "hardhat";
import { ethers } from "ethers";
import { spawn } from "node:child_process";

describe("Managed registry CLI", function () {
    this.timeout(65000);
    let server, provider, env, contract, transaction, signer;

    function cli(args, input = "", overrides = {}) {
        return new Promise((resolve, reject) => {
            const child = spawn(process.execPath, ["scripts/registry-cli.js", ...args], {
                env: { ...process.env, ...env, ...overrides }, windowsHide: true,
            });
            let output = "";
            child.stdout.on("data", data => { output += data; });
            child.once("error", reject);
            child.once("close", () => {
                try { resolve(JSON.parse(output)); } catch (error) { reject(error); }
            });
            child.stdin.end(input);
        });
    }

    before(async () => {
        server = await network.createServer(undefined, "127.0.0.1", 0);
        const { port } = await server.listen();
        const rpc = "http://127.0.0.1:" + port;
        provider = new ethers.JsonRpcProvider(rpc);
        signer = ethers.Wallet.createRandom().connect(provider);
        const faucet = await provider.getSigner(0);
        await (await faucet.sendTransaction({to: signer.address, value: ethers.parseEther("2")})).wait();
        const artifact = await artifacts.readArtifact("PermitRegistry");
        const registry = await new ethers.ContractFactory(artifact.abi, artifact.bytecode, signer).deploy();
        await registry.waitForDeployment();
        transaction = registry.deploymentTransaction().hash;
        contract = await registry.getAddress();
        env = { REGISTRY_MANAGED_CONFIG: "1", RPC_URL: rpc, REGISTRY_ADDRESS: contract,
            PRIVATE_KEY: signer.privateKey, REGISTRY_OWNER: signer.address, EXPECTED_CHAIN_ID: "31337",
            ALLOW_MAINNET_WRITES: "" };
    });

    after(async () => { await provider?.destroy(); await server?.close(); });

    it("checks creation receipt, compiled code, owner and chain before synchronization", async () => {
        const args = ["deployment", transaction, signer.address];
        expect((await cli(args)).compatible).to.equal(true);
        expect((await cli(["deployment", ethers.ZeroHash, signer.address])).ok).to.equal(false);
        expect((await cli(["deployment", transaction, ethers.ZeroAddress])).ok).to.equal(false);
        expect((await cli(args, "", {EXPECTED_CHAIN_ID: "11155111"})).ok).to.equal(false);
    });

    it("reuses a signed issuance after an interrupted response", async () => {
        const uuid = "96bf649d-79fc-4812-b1ce-e20442326444";
        const hash = "ab".repeat(32);
        const prepared = await cli(["prepare-versioned", uuid, hash, "1", hash]);
        expect(prepared.ok).to.equal(true);
        const args = ["submit-signed", prepared.txHash];
        expect((await cli(args, prepared.signed)).txHash).to.equal(prepared.txHash);
        expect((await cli(args, prepared.signed)).txHash).to.equal(prepared.txHash);
        expect((await cli(["verify", uuid, hash])).status).to.equal("valid");
    });
    it("confirms owner controls and permits revocation during a pause", async () => {
        const uuid = "4e48d28e-7972-4bbb-8714-d27fd980ac19";
        const hash = "cd".repeat(32);
        async function submit(prepared, command = "submit-signed") {
            expect(prepared.ok).to.equal(true);
            return cli([command, prepared.txHash], prepared.signed);
        }
        expect((await submit(await cli(["prepare-issue", uuid, hash]))).ok).to.equal(true);
        expect((await submit(await cli(["pause"]))).paused).to.equal(true);
        expect((await cli(["verify", uuid, hash])).status).to.equal("valid");
        expect((await submit(await cli(["prepare-revoke", uuid]), "submit-revocation")).ok).to.equal(true);
        expect((await cli(["verify", uuid, hash])).status).to.equal("revoked");
        expect((await submit(await cli(["unpause"]))).paused).to.equal(false);
    });

});
