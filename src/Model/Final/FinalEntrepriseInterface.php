<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboEntrepriseInterface;
use Aequation\LaboBundle\Model\Interface\PreferedInterface;
use Aequation\LaboBundle\Model\Interface\ScreenableInterface;
// Symfony
use Doctrine\Common\Collections\Collection;

interface FinalEntrepriseInterface extends LaboEntrepriseInterface, PreferedInterface, ScreenableInterface
{

    public function getMembers(): Collection;
    public function addMember(FinalUserInterface $member): static;
    public function hasMember(?FinalUserInterface $member = null): bool;
    public function removeMember(FinalUserInterface $member): static;

}