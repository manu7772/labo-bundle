<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Attribute\RelationOrder;
use Aequation\LaboBundle\Model\Trait\RelationOrder as TraitRelationOrder;
use Aequation\LaboBundle\Repository\EcollectionRepository;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Aequation\LaboBundle\Model\Interface\ItemInterface;
use Aequation\LaboBundle\Service\Interface\EcollectionServiceInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: EcollectionRepository::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[EA\ClassCustomService(EcollectionServiceInterface::class)]
abstract class Ecollection extends Item implements EcollectionInterface
{
    use TraitRelationOrder;

    #[ORM\ManyToMany(targetEntity: Item::class, mappedBy: 'parents', cascade: ['persist'])]
    #[RelationOrder()]
    #[Serializer\Ignore]
    protected Collection $items;

    public function __construct()
    {
        parent::__construct();
        $this->items = new ArrayCollection();
    }

    // #[Serializer\Ignore]
    // public function getItem(): ?Item
    // {
    //     $item = $this->items->first();
    //     return $item ? $item : null;
    // }

    // public function __clone()
    // {
    //     parent::__clone();
    //     // dd($this->items);
    //     $items = $this->items->toArray();
    //     $this->items = new ArrayCollection();
    //     foreach ($items as $item) {
    //         if(!$this->_service->isManaged($item)) throw new Exception(vsprintf('Error %s line %d: while cloning entity, can not add new (not persisted) items!', [__METHOD__, __LINE__]));
    //         $this->addItem($item);
    //     }
    // }

    #[Serializer\Ignore]
    public function getItems(
        bool $filterActives = false
    ): Collection
    {
        return $this->items->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()); });
        // return $this->items;
    }

    #[Serializer\Ignore]
    public function getActiveItems(): Collection
    {
        return $this->items->filter(fn($item) => $item->isActive());
    }

    #[Serializer\Ignore]
    public function addItem(ItemInterface $item): bool
    {
        if($this->isAcceptsItemForEcollection($item, 'items')) {
            if (!$this->hasItem($item)) $this->items->add($item);
            if(!$item->hasParent($this)) $item->addParent($this);
        } else {
            // not acceptable
            $this->removeItem($item);
        }
        return $this->hasItem($item);
    }

    #[Serializer\Ignore]
    public function hasItem(AppEntityInterface $item): bool
    {
        return $this->items->contains($item);
    }

    public function removeItem(ItemInterface $item): static
    {
        $this->items->removeElement($item);
        if($item->hasParent($this)) $item->removeParent($this);
        return $this;
    }

    public function removeItems(): static
    {
        foreach ($this->items->toArray() as $item) {
            $this->removeItem($item);
        }
        return $this;
    }

    #[Serializer\Ignore]
    public function isAcceptsItemForEcollection(
        AppEntityInterface $item,
        string $property
    ): bool
    {   
        if($item !== $this) {
            foreach (static::ITEMS_ACCEPT[$property] as $class) {
                if(is_a($item, $class)) return true;
            }
        }
        return false;
    }

    #[Serializer\Ignore]
    public function filterAcceptedItemsForEcollection(
        Collection $items,
        string $property
    ): Collection
    {
        return $items->filter(fn($item) => $item !== $this && $this->isAcceptsItemForEcollection($item, $property));
    }

}
