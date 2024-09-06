<?php
namespace Aequation\LaboBundle\Model\Interface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

interface ItemInterface extends AppEntityInterface
{

    public function getName(): ?string;
    public function setName(string $name): static;
    public function addParent(EcollectionInterface $parent): static;
    public function getParents(): Collection;
    public function hasParent(EcollectionInterface $parent): bool;
    public function removeParent(EcollectionInterface $parent): static;

}