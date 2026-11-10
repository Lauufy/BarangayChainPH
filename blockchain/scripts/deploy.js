const hre = require('hardhat');

async function main() {
  const [deployer] = await hre.ethers.getSigners();
  console.log(`Deploying from ${deployer.address} on ${hre.network.name}...`);

  const registry = await hre.ethers.deployContract('PermitRegistry');
  await registry.waitForDeployment();

  const address = await registry.getAddress();
  console.log(`PermitRegistry deployed to: ${address}`);
  console.log(`Deploy transaction: ${registry.deploymentTransaction().hash}`);
  console.log('');
  console.log('Next: add this line to blockchain/.env');
  console.log(`REGISTRY_ADDRESS=${address}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});