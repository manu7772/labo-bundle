<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Model\Final\FinalCategoryInterface;
use Doctrine\Common\Collections\Collection;

interface LaboRelinkInterface extends ItemInterface, SlugInterface, PreferedInterface
{
    public function isUrl(): bool;
    public function isRoute(): bool;
    public function isAddress(): bool;
    public function isEmail(): bool;
    public function isPhone(): bool;
    // Relink type
    public function getRelinkType(): ?string;
    public function getRelinkTypeChoices(): array;
    public function getMainlink(): ?string;
    public function setMainlink(?string $mainlink): static;
    public function getParams(): ?array;
    public function setParams(?array $params): static;
    public function getTargetChoices(): array;
    public function getTarget(): ?string;
    public function setTarget(?string $target): static;
    public function getParentrelink(): ?static;
    public function setParentrelink(?LaboRelinkInterface $parentrelink): static;
    public function getRelinks(): Collection;
    public function addRelink(LaboRelinkInterface $child): static;
    public function removeRelink(LaboRelinkInterface $child): static;
    public function setTurboenabled(bool $turboenabled = true): static;
    public function isTurboenabled(): bool;
    public function getLinktitle(): ?string;
    public function setLinktitle(?string $linktitle): static;
    public function getCategorys(): Collection;
    public function addCategory(FinalCategoryInterface $category): static;
    public function removeCategory(FinalCategoryInterface $category): static;
    public function removeCategorys(): static;
}