<?php
namespace Aequation\LaboBundle\Model\Interface;

interface SlugInterface extends AppEntityInterface
{
    public function __construct_slug(): void;
    public function __clone_slug(): void;
    public function getSlug(): ?string;
    public function setSlug(string $slug): static;
    public function setUpdateSlug(bool $updateSlug): static;
    public function isUpdateSlug(): bool;
}