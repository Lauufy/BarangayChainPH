const hre = require('hardhat');

async function main() {
  const [deployer] = await hre.ethers.getSigners();
  const network = await hre.ethers.provider.getNetwork();

  console.log(`Deploying from ${deployer.address}`);
  console.log(`Network: ${hre.network.name}, chain ID: ${network.chainId}`);

  const registry = await hre.ethers.deployContract('PermitRegistry');
  await registry.waitForDeployment();

  const address = await registry.getAddress();
  console.log(`PermitRegistry deployed to: ${address}`);
  console.log(`Deploy transaction: ${registry.deploymentTransaction().hash}`);
  console.log('');
  console.log('Next: put these in the Laravel .env (public values, safe to note down):');
  console.log(`BLOCKCHAIN_CONTRACT=${address}`);
  console.log(`BLOCKCHAIN_CHAIN_ID=${network.chainId}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});