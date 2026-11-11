require("@nomicfoundation/hardhat-toolbox");
require("dotenv").config({ quiet: true });

const { RPC_URL, PRIVATE_KEY, ETHERSCAN_API_KEY } = process.env;

module.exports = {
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
                  sepolia: { url: RPC_URL, accounts: [PRIVATE_KEY] },
                  live: { url: RPC_URL, accounts: [PRIVATE_KEY] },
              }
            : {}),
    },
    etherscan: {
        apiKey: ETHERSCAN_API_KEY || "",
    },
};
