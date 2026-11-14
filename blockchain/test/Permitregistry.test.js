const { expect } = require('chai');
const { ethers } = require('hardhat');

// Same numbering as the Status enum in PermitRegistry.sol
const Status = { NotFound: 0n, Valid: 1n, Revoked: 2n, Mismatch: 3n };

describe('PermitRegistry', () => {
  const documentId = ethers.keccak256(ethers.toUtf8Bytes('1b9d6bcd-bbfd-4b2d-9b5d-ab8dfbbd4bed'));
  const fileHash = ethers.sha256(ethers.toUtf8Bytes('permit-pdf-bytes'));
  const tamperedHash = ethers.sha256(ethers.toUtf8Bytes('tampered-pdf-bytes'));

  let registry;
  let owner;
  let staff;
  let stranger;

  beforeEach(async () => {
    [owner, staff, stranger] = await ethers.getSigners();
    registry = await ethers.deployContract('PermitRegistry');
  });

  describe('setup', () => {
    it('makes the deployer the owner and an issuer', async () => {
      expect(await registry.owner()).to.equal(owner.address);
      expect(await registry.isIssuer(owner.address)).to.equal(true);
    });

    it('lets only the owner add or remove issuers', async () => {
      await expect(registry.connect(stranger).setIssuer(staff.address, true))
        .to.be.revertedWithCustomError(registry, 'NotOwner');

      await registry.setIssuer(staff.address, true);
      expect(await registry.isIssuer(staff.address)).to.equal(true);

      await registry.setIssuer(staff.address, false);
      expect(await registry.isIssuer(staff.address)).to.equal(false);
    });

    it('transfers ownership in two steps and removes the previous owner as an issuer', async () => {
      await expect(registry.transferOwnership(staff.address))
        .to.emit(registry, 'OwnershipTransferStarted')
        .withArgs(owner.address, staff.address);

      await expect(registry.connect(stranger).acceptOwnership())
        .to.be.revertedWithCustomError(registry, 'NotPendingOwner');

      await expect(registry.connect(staff).acceptOwnership())
        .to.emit(registry, 'OwnershipTransferred')
        .withArgs(owner.address, staff.address);

      expect(await registry.owner()).to.equal(staff.address);
      expect(await registry.pendingOwner()).to.equal(ethers.ZeroAddress);
      expect(await registry.isIssuer(owner.address)).to.equal(false);
    });

    it('lets the owner cancel a pending ownership transfer', async () => {
      await registry.transferOwnership(staff.address);

      await expect(registry.cancelOwnershipTransfer())
        .to.emit(registry, 'OwnershipTransferCancelled')
        .withArgs(owner.address, staff.address);

      await expect(registry.connect(staff).acceptOwnership())
        .to.be.revertedWithCustomError(registry, 'NotPendingOwner');
    });

    it('restricts ownership transfer controls to the current owner', async () => {
      await expect(registry.connect(stranger).transferOwnership(staff.address))
        .to.be.revertedWithCustomError(registry, 'NotOwner');

      await registry.transferOwnership(staff.address);

      await expect(registry.connect(stranger).cancelOwnershipTransfer())
        .to.be.revertedWithCustomError(registry, 'NotOwner');
    });

    it('rejects zero-address and self ownership transfers', async () => {
      await expect(registry.transferOwnership(ethers.ZeroAddress))
        .to.be.revertedWithCustomError(registry, 'ZeroAddress');

      await expect(registry.transferOwnership(owner.address))
        .to.be.revertedWithCustomError(registry, 'SameOwner');
    });

    it('rejects the zero address as an issuer', async () => {
      await expect(registry.setIssuer(ethers.ZeroAddress, true))
        .to.be.revertedWithCustomError(registry, 'ZeroAddress');
    });
  });

  describe('issuing', () => {
    it('stores the hash and emits an event', async () => {
      await expect(registry.issue(documentId, fileHash)).to.emit(registry, 'DocumentIssued');

      const record = await registry.getRecord(documentId);
      expect(record.exists).to.equal(true);
      expect(record.fileHash).to.equal(fileHash);
      expect(record.issuer).to.equal(owner.address);
      expect(record.issuedAt).to.be.greaterThan(0n);
      expect(record.revokedAt).to.equal(0n);
    });

    it('finds an issuance event using its indexed document id', async () => {
      const transaction = await registry.issue(documentId, fileHash);
      const receipt = await transaction.wait();

      const events = await registry.queryFilter(
        registry.filters.DocumentIssued(documentId),
        0,
        'latest',
      );

      expect(events).to.have.length(1);
      expect(events[0].transactionHash).to.equal(receipt.hash);
      expect(events[0].blockNumber).to.equal(receipt.blockNumber);
    });

    it('rejects a second issuance of the same document id', async () => {
      await registry.issue(documentId, fileHash);

      await expect(registry.issue(documentId, tamperedHash))
        .to.be.revertedWithCustomError(registry, 'AlreadyIssued');
    });

    it('rejects an empty hash', async () => {
      await expect(registry.issue(documentId, ethers.ZeroHash))
        .to.be.revertedWithCustomError(registry, 'EmptyHash');
    });

    it('rejects accounts that are not issuers', async () => {
      await expect(registry.connect(stranger).issue(documentId, fileHash))
        .to.be.revertedWithCustomError(registry, 'NotIssuer');
    });

    it('lets an issuer added by the owner issue documents', async () => {
      await registry.setIssuer(staff.address, true);
      await registry.connect(staff).issue(documentId, fileHash);

      expect((await registry.getRecord(documentId)).issuer).to.equal(staff.address);
    });
  });

  describe('verifying', () => {
    it('reports not found for an unknown document', async () => {
      expect(await registry.verify(documentId, fileHash)).to.equal(Status.NotFound);
    });

    it('reports valid when the hash matches', async () => {
      await registry.issue(documentId, fileHash);

      expect(await registry.verify(documentId, fileHash)).to.equal(Status.Valid);
    });

    it('reports mismatch when the file was changed', async () => {
      await registry.issue(documentId, fileHash);

      expect(await registry.verify(documentId, tamperedHash)).to.equal(Status.Mismatch);
    });
  });

  describe('revoking', () => {
    beforeEach(async () => {
      await registry.issue(documentId, fileHash);
    });

    it('marks the document revoked and emits an event', async () => {
      await expect(registry.revoke(documentId)).to.emit(registry, 'DocumentRevoked');

      expect((await registry.getRecord(documentId)).revokedAt).to.be.greaterThan(0n);
      expect(await registry.verify(documentId, fileHash)).to.equal(Status.Revoked);
    });

    it('reports revoked even when the hash does not match', async () => {
      await registry.revoke(documentId);

      expect(await registry.verify(documentId, tamperedHash)).to.equal(Status.Revoked);
    });

    it('cannot be done twice', async () => {
      await registry.revoke(documentId);

      await expect(registry.revoke(documentId))
        .to.be.revertedWithCustomError(registry, 'AlreadyRevoked');
    });

    it('fails for a document that was never issued', async () => {
      const unknownId = ethers.keccak256(ethers.toUtf8Bytes('never-issued'));

      await expect(registry.revoke(unknownId))
        .to.be.revertedWithCustomError(registry, 'DocumentNotFound');
    });

    it('is not allowed for accounts that are not issuers', async () => {
      await expect(registry.connect(stranger).revoke(documentId))
        .to.be.revertedWithCustomError(registry, 'NotIssuer');
    });

    it('does not let an issuer revoke a document issued by another account', async () => {
      await registry.setIssuer(staff.address, true);

      await expect(registry.connect(staff).revoke(documentId))
        .to.be.revertedWithCustomError(registry, 'NotDocumentIssuer');
    });

    it('lets the owner revoke a document even after losing issuer status', async () => {
      const staffDocumentId = ethers.keccak256(ethers.toUtf8Bytes('staff-issued'));
      await registry.setIssuer(staff.address, true);
      await registry.connect(staff).issue(staffDocumentId, fileHash);
      await registry.setIssuer(owner.address, false);

      await registry.revoke(staffDocumentId);

      expect(await registry.verify(staffDocumentId, fileHash)).to.equal(Status.Revoked);
    });
  });
});