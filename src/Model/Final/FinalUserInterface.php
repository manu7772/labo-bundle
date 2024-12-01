<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Doctrine\Common\Collections\Collection;

interface FinalUserInterface extends LaboUserInterface
{

    public function getEntreprises(): Collection;
    public function addEntreprise(FinalEntrepriseInterface $entreprise): static;
    public function hasEntreprise(FinalEntrepriseInterface $entreprise = null): bool;
    public function removeEntreprise(FinalEntrepriseInterface $entreprise): static;
    public function isAdmin(): bool;
    /** ACTIONS */
    public function getMainentreprise(): bool;
    public function getComputedMainentreprise(): bool;
    public function setMainentreprise(bool $mainentreprise): static;
    public function isCheckMainentreprise(): bool;

}