<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Doctrine\Common\Collections\Collection;

interface FinalEntrepriseInterface extends LaboUserInterface
{

    public function getMembers(): Collection;
    public function addMember(FinalUserInterface $member): static;
    public function hasMember(FinalUserInterface $member = null): bool;
    public function removeMember(FinalUserInterface $member): static;

}