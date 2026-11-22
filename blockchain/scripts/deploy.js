import { network } from "hardhat";

async function main() {
  const { ethers, networkName } = await network.create();
  const [deployer] = await ethers.getSigners();
  const chain = await ethers.provider.getNetwork();

  console.log(`Deploying from ${deployer.address}`);
  console.log(`Network: ${networkName}, chain ID: ${chain.chainId}`);

  const registry = await ethers.deployContract("PermitRegistry");
  await registry.waitForDeployment();

  const deployTx = registry.deploymentTransaction();
  if (networkName !== "default" && networkName !== "hardhat" && networkName !== "localhost") {
    await deployTx.wait(2); // wait for 2 confirmations on real networks
  }

  const address = await registry.getAddress();
  console.log(`PermitRegistry deployed to: ${address}`);
  console.log(`Deploy transaction: ${deployTx.hash}`);
  console.log("");
  console.log("Laravel .env (public values, safe to note down):");
  console.log(`BLOCKCHAIN_CONTRACT=${address}`);
  console.log(`BLOCKCHAIN_CHAIN_ID=${chain.chainId}`);
  console.log(`BLOCKCHAIN_NETWORK=${networkName}`);
  console.log("");
  console.log("blockchain/.env:");
  console.log(`REGISTRY_ADDRESS=${address}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});