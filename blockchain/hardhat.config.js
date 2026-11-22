import hardhatToolboxMochaEthersPlugin from "@nomicfoundation/hardhat-toolbox-mocha-ethers";
import { defineConfig } from "hardhat/config";
import dotenv from "dotenv";

dotenv.config({ quiet: true });

const { RPC_URL, PRIVATE_KEY, ETHERSCAN_API_KEY } = process.env;

export default defineConfig({
    plugins: [hardhatToolboxMochaEthersPlugin],
    solidity: {
        version: "0.8.24",
        settings: {
            optimizer: { enabled: true, runs: 200 },
            // "paris" avoids opcodes that some test networks may not support.
            evmVersion: "paris",
        },
    },
    networks: {
        // Only defined when both values are set, so `npx hardhat test` works without a .env file.
        // "live" means whatever network RPC_URL points to. "sepolia" stays so old commands still work.
        ...(RPC_URL && PRIVATE_KEY
            ? {
                  sepolia: {
                      type: "http",
                      chainType: "l1",
                      url: RPC_URL,
                      accounts: [PRIVATE_KEY],
                  },
                  live: {
                      type: "http",
                      chainType: "l1",
                      url: RPC_URL,
                      accounts: [PRIVATE_KEY],
                  },
              }
            : {}),
    },
    verify: {
        etherscan: {
            apiKey: ETHERSCAN_API_KEY || "",
        },
    },
});
