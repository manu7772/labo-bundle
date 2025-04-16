<?php
namespace Aequation\LaboBundle\Model\Interface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

interface EcollectionInterface extends ItemInterface, HasRelationOrderInterface
{

    public function setItems(array|Collection $items): static;
    public function getItems(): Collection;
    public function addItem(ItemInterface $item): bool;
    public function removeItem(ItemInterface $item): static;
    public function removeItems(): static;
    public function hasItem(AppEntityInterface $item): bool;

    public function isAcceptsItemForEcollection(AppEntityInterface $item, string $property): bool;
    public function filterAcceptedItemsForEcollection(Collection $items, string $property): Collection;

}

