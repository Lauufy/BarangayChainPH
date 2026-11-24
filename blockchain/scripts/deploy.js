import { network } from "hardhat";

async function main() {
  const { ethers, networkName } = await network.create();
  const [deployer] = await ethers.getSigners();
  const chain = await ethers.provider.getNetwork();

  if (chain.chainId === 1n && (networkName !== "mainnet" || process.env.ALLOW_MAINNET_DEPLOY !== "1")) {
    throw new Error("Mainnet deployment requires --network mainnet and ALLOW_MAINNET_DEPLOY=1.");
  }

  console.log(`Deploying from ${deployer.address}`);
  console.log(`Network: ${networkName}, chain ID: ${chain.chainId}`);

  const registry = await ethers.deployContract("PermitRegistry");
  await registry.waitForDeployment();

  const deployTx = registry.deploymentTransaction();
  if (networkName !== "default" && networkName !== "hardhat" && networkName !== "localhost") {
    await deployTx.wait(chain.chainId === 1n ? 3 : 2);
  }

  const address = await registry.getAddress();
  console.log(`PermitRegistry deployed to: ${address}`);
  console.log(`Deploy transaction: ${deployTx.hash}`);
  console.log("");
  console.log("Laravel .env (public values, safe to note down):");
  console.log(`BLOCKCHAIN_${chain.chainId === 1n ? "MAINNET" : "SEPOLIA"}_CONTRACT=${address}`);
  console.log("");
  console.log("blockchain/.env:");
  console.log(`REGISTRY_ADDRESS=${address}`);
}

main().catch((error) => {
  console.error(error.code ? "Deployment failed (" + error.code + ")" : error.message);
  process.exitCode = 1;
});