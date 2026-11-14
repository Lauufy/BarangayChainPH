const { expect } = require("chai");
const { ethers } = require("hardhat");

describe("PermitRegistry pause", () => {
  let registry, owner, stranger;
  const docA = ethers.keccak256(ethers.toUtf8Bytes("doc-a"));
  const docB = ethers.keccak256(ethers.toUtf8Bytes("doc-b"));
  const hash = ethers.keccak256(ethers.toUtf8Bytes("file"));

  beforeEach(async () => {
    [owner, stranger] = await ethers.getSigners();
    registry = await ethers.deployContract("PermitRegistry");
    await registry.waitForDeployment();
  });

  it("starts unpaused", async () => {
    expect(await registry.paused()).to.equal(false);
  });

  it("lets only the owner pause", async () => {
    await expect(registry.connect(stranger).setPaused(true))
      .to.be.revertedWithCustomError(registry, "NotOwner");
    await expect(registry.setPaused(true))
      .to.emit(registry, "PauseChanged").withArgs(true, owner.address);
    expect(await registry.paused()).to.equal(true);
  });

  it("blocks issue while paused", async () => {
    await registry.setPaused(true);
    await expect(registry.issue(docA, hash))
      .to.be.revertedWithCustomError(registry, "ContractPaused");
  });

  it("still allows revoke and verify while paused", async () => {
    await registry.issue(docA, hash);
    await registry.setPaused(true);
    expect(await registry.verify(docA, hash)).to.equal(1n); // Valid
    await registry.revoke(docA);
    expect(await registry.verify(docA, hash)).to.equal(2n); // Revoked
  });

  it("allows issuing again after resume", async () => {
    await registry.setPaused(true);
    await registry.setPaused(false);
    await registry.issue(docB, hash);
    expect(await registry.verify(docB, hash)).to.equal(1n);
  });
});