<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\PreferedInterface;
use Aequation\LaboBundle\Model\Interface\ScreenableInterface;
// Symfony
use Doctrine\Common\Collections\Collection;

interface FinalEntrepriseInterface extends LaboUserInterface, PreferedInterface, ScreenableInterface
{

    public function getMembers(): Collection;
    public function addMember(FinalUserInterface $member): static;
    public function hasMember(?FinalUserInterface $member = null): bool;
    public function removeMember(FinalUserInterface $member): static;

}