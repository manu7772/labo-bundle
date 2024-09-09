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

}