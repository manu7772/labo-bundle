<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Model\Interface\CreatedInterface;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
// Symfony
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\EquatableInterface;


interface FinalUserInterface extends LaboUserInterface, EquatableInterface, ImageOwnerInterface, UnamedInterface, EnabledInterface, CreatedInterface
{

    public function getEntreprises(): Collection;
    public function addEntreprise(FinalEntrepriseInterface $entreprise): static;
    public function hasEntreprise(?FinalEntrepriseInterface $entreprise = null): bool;
    public function removeEntreprise(FinalEntrepriseInterface $entreprise): static;
    public function isAdmin(): bool;
    /** ACTIONS */
    public function memorizeMainentrepriseAfterLoad(): static;
    public function wasMainentreprise(): bool;
    public function getMainentreprise(): bool;
    public function getComputedMainentreprise(): bool;
    public function setMainentreprise(bool $mainentreprise): static;
    public function isCheckMainentreprise(): bool;

}