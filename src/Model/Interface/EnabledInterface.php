<?php
namespace Aequation\LaboBundle\Model\Interface;

interface EnabledInterface extends AppEntityInterface
{
    public function __construct_enabled(): void;
    public function __clone_enabled(): void;
    public function isActive(): bool;
    public function isEnabled(): ?bool;
    public function setEnabled(bool $enabled): static;
    public function isSoftdeleted(): ?bool;
    public function setSoftdeleted(bool $softdeleted): static;
}