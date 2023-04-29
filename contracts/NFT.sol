// SPDX-License-Identifier: MIT
pragma solidity ^0.8.4;

import "@openzeppelin/contracts/token/ERC721/ERC721.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/utils/Counters.sol";

contract NFT is ERC721, Ownable {
    using Counters for Counters.Counter;

    Counters.Counter private _tokenIdCounter;
    mapping(uint256 => string) internal _tokenURIs;
    mapping(uint256 => address) internal _tokenAddress;

    constructor(string memory name, string memory symbol) ERC721(name, symbol) {
        _transferOwnership(tx.origin);
    }

    function safeMint(
        uint256 tokenId,
        address to,
        string memory uri
    ) public onlyOwner {
        _safeMint(to, tokenId);
        _tokenURIs[tokenId] = uri;
    }

    function tokenURI(
        uint256 tokenId
    ) public view override returns (string memory) {
        return _tokenURIs[tokenId];
    }

    function _burn(uint256 tokenId) internal override(ERC721) {
        delete _tokenURIs[tokenId];
        super._burn(tokenId);
    }
}
