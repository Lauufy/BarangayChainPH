// Shows which network and wallet Hardhat is using. It spends nothing.
const hre = require('hardhat');

async function main() {
  const [signer] = await hre.ethers.getSigners();
  const network = await hre.ethers.provider.getNetwork();
  const balance = await hre.ethers.provider.getBalance(signer.address);

  console.log(`Network:  ${hre.network.name}`);
  console.log(`Chain ID: ${network.chainId}`);
  console.log(`Wallet:   ${signer.address}`);
  console.log(`Balance:  ${hre.ethers.formatEther(balance)}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});