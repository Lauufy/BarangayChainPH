const hre = require('hardhat');

async function main() {
  const [deployer] = await hre.ethers.getSigners();
  const network = await hre.ethers.provider.getNetwork();

  console.log(`Deploying from ${deployer.address}`);
  console.log(`Network: ${hre.network.name}, chain ID: ${network.chainId}`);

  const registry = await hre.ethers.deployContract('PermitRegistry');
  await registry.waitForDeployment();

  const deployTx = registry.deploymentTransaction();
  if (hre.network.name !== 'hardhat' && hre.network.name !== 'localhost') {
    await deployTx.wait(2); // wait for 2 confirmations on real networks
  }

  const address = await registry.getAddress();
  console.log(`PermitRegistry deployed to: ${address}`);
  console.log(`Deploy transaction: ${deployTx.hash}`);
  console.log('');
  console.log('Laravel .env (public values, safe to note down):');
  console.log(`BLOCKCHAIN_CONTRACT=${address}`);
  console.log(`BLOCKCHAIN_CHAIN_ID=${network.chainId}`);
  console.log(`BLOCKCHAIN_NETWORK=${hre.network.name}`);
  console.log('');
  console.log('blockchain/.env:');
  console.log(`REGISTRY_ADDRESS=${address}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});