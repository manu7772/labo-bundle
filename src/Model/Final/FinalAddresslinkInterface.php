<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;

interface FinalAddresslinkInterface extends LaboRelinkInterface
{
    /**
     * Get complete address as array of lines
     */
    public function getAddressLines(bool $joinCPandVille = true): array;
    /**
     * Get address as map link (google map, etc.)
     */
    public function getMaplink(): string;

    public function setAddress(string $address): static;
    public function getAddress(): string;
    public function setVille(?string $ville): static;
    public function getVille(): ?string;
    public function setCodePostal(?string $codePostal): static;
    public function getCodePostal(): ?string;
}