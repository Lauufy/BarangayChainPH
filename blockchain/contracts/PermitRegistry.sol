// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/// @title PermitRegistry
/// @notice Anchors the SHA-256 hash of each issued barangay document and tracks revocation.
/// @dev Stores no personal data: only an opaque document id and a file hash.
///      documentId = keccak256(lowercase document UUID), fileHash = SHA-256 of the issued PDF.
contract PermitRegistry {
    enum Status {
        NotFound,
        Valid,
        Revoked,
        Mismatch
    }

    struct Record {
        bytes32 fileHash;
        address issuer;
        uint64 issuedAt;
        uint64 revokedAt; // 0 means not revoked
        bool exists;
    }

    address public owner;
    address public pendingOwner;
    bool public paused; // when true, new documents cannot be issued
    mapping(address => bool) public isIssuer;
    mapping(bytes32 => Record) private records;

    event OwnershipTransferStarted(address indexed owner, address indexed pendingOwner);
    event OwnershipTransferCancelled(address indexed owner, address indexed pendingOwner);
    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);
    event IssuerChanged(address indexed account, bool allowed);
    event PauseChanged(bool paused, address indexed by);
    event DocumentIssued(bytes32 indexed documentId, bytes32 fileHash, address indexed issuer, uint64 issuedAt);
    event DocumentRevoked(bytes32 indexed documentId, address indexed revokedBy, uint64 revokedAt);

    error NotOwner();
    error NotIssuer();
    error NotDocumentIssuer();
    error NotPendingOwner();
    error ZeroAddress();
    error SameOwner();
    error ContractPaused();
    error EmptyHash();
    error AlreadyIssued();
    error DocumentNotFound();
    error AlreadyRevoked();

    modifier onlyOwner() {
        if (msg.sender != owner) revert NotOwner();
        _;
    }

    modifier onlyIssuer() {
        if (!isIssuer[msg.sender]) revert NotIssuer();
        _;
    }

    modifier whenNotPaused() {
        if (paused) revert ContractPaused();
        _;
    }

    constructor() {
        owner = msg.sender;
        isIssuer[msg.sender] = true;
        emit OwnershipTransferred(address(0), msg.sender);
        emit IssuerChanged(msg.sender, true);
    }

    /// @notice Start a two-step ownership transfer. The new owner must accept it.
    function transferOwnership(address newOwner) external onlyOwner {
        if (newOwner == address(0)) revert ZeroAddress();
        if (newOwner == owner) revert SameOwner();

        pendingOwner = newOwner;
        emit OwnershipTransferStarted(owner, newOwner);
    }

    /// @notice Cancel an ownership transfer that has not yet been accepted.
    function cancelOwnershipTransfer() external onlyOwner {
        address cancelledOwner = pendingOwner;
        pendingOwner = address(0);
        emit OwnershipTransferCancelled(owner, cancelledOwner);
    }

    /// @notice Accept ownership and remove the previous owner's issuer permission.
    function acceptOwnership() external {
        if (msg.sender != pendingOwner) revert NotPendingOwner();

        address previousOwner = owner;
        owner = msg.sender;
        pendingOwner = address(0);

        if (isIssuer[previousOwner]) {
            isIssuer[previousOwner] = false;
            emit IssuerChanged(previousOwner, false);
        }

        emit OwnershipTransferred(previousOwner, msg.sender);
    }

    /// @notice Allow or remove an account that may issue and revoke documents.
    function setIssuer(address account, bool allowed) external onlyOwner {
        if (account == address(0)) revert ZeroAddress();

        isIssuer[account] = allowed;
        emit IssuerChanged(account, allowed);
    }

    /// @notice Emergency switch. While paused, no new documents can be issued.
    /// @dev Revoking, verifying and reading records keep working during a pause.
    function setPaused(bool state) external onlyOwner {
        paused = state;
        emit PauseChanged(state, msg.sender);
    }

    /// @notice Record a newly issued document. A document id can only be issued once.
    function issue(bytes32 documentId, bytes32 fileHash) external onlyIssuer whenNotPaused {
        if (fileHash == bytes32(0)) revert EmptyHash();

        Record storage r = records[documentId];
        if (r.exists) revert AlreadyIssued();

        r.fileHash = fileHash;
        r.issuer = msg.sender;
        r.issuedAt = uint64(block.timestamp);
        r.exists = true;

        emit DocumentIssued(documentId, fileHash, msg.sender, r.issuedAt);
    }

    /// @notice Mark an issued document as revoked. This cannot be undone.
    function revoke(bytes32 documentId) external {
        if (msg.sender != owner && !isIssuer[msg.sender]) revert NotIssuer();

        Record storage r = records[documentId];
        if (!r.exists) revert DocumentNotFound();
        if (r.revokedAt != 0) revert AlreadyRevoked();
        if (msg.sender != owner && msg.sender != r.issuer) revert NotDocumentIssuer();

        r.revokedAt = uint64(block.timestamp);

        emit DocumentRevoked(documentId, msg.sender, r.revokedAt);
    }

    /// @notice Compare a file hash with what was anchored on chain.
    /// @dev Revoked wins over Mismatch, so a revoked document always reports as revoked.
    function verify(bytes32 documentId, bytes32 fileHash) external view returns (Status) {
        Record storage r = records[documentId];

        if (!r.exists) return Status.NotFound;
        if (r.revokedAt != 0) return Status.Revoked;
        if (r.fileHash != fileHash) return Status.Mismatch;

        return Status.Valid;
    }

    /// @notice Read the stored record for a document.
    function getRecord(bytes32 documentId)
        external
        view
        returns (bool exists, bytes32 fileHash, address issuer, uint64 issuedAt, uint64 revokedAt)
    {
        Record storage r = records[documentId];
        return (r.exists, r.fileHash, r.issuer, r.issuedAt, r.revokedAt);
    }
}