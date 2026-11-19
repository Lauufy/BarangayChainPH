// Shows which network and wallet Hardhat is using. It spends nothing.
import { network } from "hardhat";

async function main() {
  const { ethers, networkName } = await network.create();
  const [signer] = await ethers.getSigners();
  const chain = await ethers.provider.getNetwork();
  const balance = await ethers.provider.getBalance(signer.address);

  console.log(`Network:  ${networkName}`);
  console.log(`Chain ID: ${chain.chainId}`);
  console.log(`Wallet:   ${signer.address}`);
  console.log(`Balance:  ${ethers.formatEther(balance)}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});