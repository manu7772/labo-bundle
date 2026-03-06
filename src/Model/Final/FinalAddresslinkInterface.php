<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;
// PHP
use Twig\Markup;

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

    public function getAddressOneLine(): Markup;
    public function setAddress(string $address): static;
    public function getAddress(): string;
    public function setLines(array $lines): static;
    public function getLines(): array;
    public function setVille(?string $ville): static;
    public function getVille(): ?string;
    public function setCodePostal(?string $codePostal): static;
    public function getCodePostal(): ?string;
    public function setUrl(?string $url): static;
    public function getUrl(): ?string;
    public function setGps(?array $gps): static;
    public function getGps(): ?array;
}