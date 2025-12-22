<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Attribute\RelationOrder;
use Aequation\LaboBundle\Repository\EcollectionRepository;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Aequation\LaboBundle\Model\Interface\ItemInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\EcollectionServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use ReflectionProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: EcollectionRepository::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[EA\ClassCustomService(EcollectionServiceInterface::class)]
abstract class Ecollection extends Item implements EcollectionInterface
{
    public const RELATION_FIELDNAME = 'items';
    public const KEEP_ORDERED_INDEXES = false;
    public const ITEMS_ACCEPT = [
        'pdfiles' => [Pdf::class],
    ];

    #[ORM\Column]
    #[Serializer\Ignore]
    protected ?array $relationOrder = [];

    #[Serializer\Ignore]
    protected bool $isDirtyOrder = true;

    #[ORM\ManyToMany(targetEntity: Item::class, mappedBy: 'parents', cascade: ['persist'])]
    #[ORM\OrderBy(['orderitem' => 'ASC', 'id' => 'ASC'])]
    #[RelationOrder()]
    // #[Serializer\Groups('detail')]
    #[Serializer\MaxDepth(1)]
    protected Collection $items;

    public function __construct()
    {
        parent::__construct();
        $this->items = new ArrayCollection();
    }

    #[Serializer\Ignore]
    public function getItems(
        bool $filterActives = false
    ): Collection
    {
        return $this->items->filter(function ($item) use ($filterActives) { return (!$filterActives || $item->isActive()); });
    }

    #[Serializer\Ignore]
    public function getActiveItems(): Collection
    {
        return $this->items->filter(fn($item) => $item->isActive());
    }

    public function setItems(array|Collection $items): static
    {
        $this->removeItems();
        foreach($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }

    #[Serializer\Ignore]
    public function addItem(ItemInterface $item): bool
    {
        if($this->_isModel() || $item->_isModel()) {
            // Cannot add parent to model
            return false;
        }
        if($this->isAcceptsItemForEcollection($item, 'items')) {
            if (!$this->hasItem($item)) $this->items->add($item);
            if(!$item->hasParent($this)) $item->addParent($this);
            // dump('Adding "'.$item.'" to '.$this->__toString().' (id:'.spl_object_id($this).')...', $item);
        } else {
            // not acceptable
            $this->removeItem($item);
            return $this->hasItem($item);
        }
        // dump("Item $item ".($this->hasItem($item) ? '' : 'NOT ')."added to ".$this->__toString().' (id:'.spl_object_id($this).')');
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

    // #[ORM\PrePersist]
    // #[ORM\PreUpdate]
    #[AppEvent(groups: FormEvents::POST_SUBMIT)]
    #[AppEvent(groups: AppEvent::beforePrePersist)]
    #[AppEvent(groups: AppEvent::beforePreUpdate)]
    public function updateRelationOrder(
        $event = null
    ): bool
    {
        $attributes = Classes::getPropertyAttributes($this, RelationOrder::class, true);
        if(empty($attributes)) throw new Exception(vsprintf('Error %s line %d: no field found for %s in entity %s!', [__METHOD__, __LINE__, RelationOrder::class, $this->getClassname()]));
        $old = $this->getRelationOrder();
        ksort($old);
        $old = json_encode($old);
        $new = [];
        foreach ($attributes as $properties) {
            foreach ($properties as $attribute) {
                $property = $attribute->property->name;
                if(isset($new[$property])) throw new Exception(vsprintf('Error %s line %d: property "%s" already defined for %s attribute!', [__METHOD__, __LINE__, $property, RelationOrder::class]));
                $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
                $property_elements = $propertyAccessor->getValue($this, $property);
                $new[$property] = [];
                foreach($property_elements as $rel) {
                    /** @var AppEntityInterface $rel */
                    $new[$property][] = $rel->getEuid();
                }
            }
        }
        ksort($new);
        $this->isDirtyOrder = false;
        if($old !== json_encode($new)) {
            $this->relationOrder = $new;
            // $this->_appManaged->setRelationOrderLoaded(false);
            $this->loadedRelationOrder(force: true);
            return true;
        }
        return false;
    }

    #[AppEvent(groups: AppEvent::onLoad)]
    public function loadedRelationOrder(
        ?AppEntityManagerInterface $manager = null,
        array $params = [],
        bool $force = false
    ): static
    {
        if($force || !$this->_appManaged->isRelationOrderLoaded(false)) {
            $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
            foreach($this->getRelationOrder() as $property => $values) {
                $property_elements = $propertyAccessor->getValue($this, $property);
                $collection = new ArrayCollection();
                try {
                    foreach ($property_elements as $item) {
                        $collection->set($item->getEuid(), $item);
                        $property_elements->removeElement($item);
                        $rest = clone $property_elements;
                        $property_elements->clear();
                    }
                    foreach ($values as $euid) {
                        if($item = $collection->get($euid)) {
                            if(static::KEEP_ORDERED_INDEXES) {
                                $property_elements->set($euid, $item);
                            } else if(!$property_elements->contains($item)) {
                                $property_elements->add($item);
                            }
                        }
                    }
                    if(isset($rest) && !$rest->isEmpty()) {
                        foreach ($rest as $item) {
                            if(static::KEEP_ORDERED_INDEXES) {
                                $property_elements->set($item->getEuid(), $item);
                            } else if(!$property_elements->contains($item)) {
                                $property_elements->add($item);
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    // dd($this, $property_elements, $th);
                }
            }
            $this->_appManaged->setRelationOrderLoaded(true);
        }
        return $this;
    }

    #[Serializer\Ignore]
    public function getRelationOrderDetails(): string
    {
        return json_encode($this->relationOrder);
    }

    #[Serializer\Ignore]
    public function getRelationOrderNames(
        string|ReflectionProperty|null $property = null
    ): array
    {
        if($property instanceof ReflectionProperty) $property = $property->name;
        $names = [];
        foreach (array_keys($this->relationOrder) as $prop) {
            foreach ($this->$prop as $item) {
                if(empty($property)) {
                    $names[] = '['.$prop.']'.$item->__toString();
                } else if($prop === $property) {
                    $names[] = $item->__toString();
                }
            }
        }
        return $names;
    }

    #[Serializer\Ignore]
    public function getRelationOrder(
        bool $asJson = false
    ): array|string|false
    {
        $relationOrder = $this->relationOrder ??= [];
        return $asJson ? json_encode($relationOrder) : $relationOrder;
    }

    #[Serializer\Ignore]
    public function getPropRelationOrder(
        string|ReflectionProperty $property
    ): array
    {
        if($property instanceof ReflectionProperty) $property = $property->name;
        return $this->relationOrder[$property];
    }

    // public function setRelationOrder(array $relationOrder): static
    // {
    //     $this->relationOrder = $relationOrder;
    //     return $this;
    // }

    // public function setPropRelationOrder(string|ReflectionProperty $property, array $relationOrder): static
    // {
    //     if($property instanceof ReflectionProperty) $property = $property->name;
    //     $this->relationOrder[$property] = $relationOrder;
    //     return $this;
    // }

    public function changePosition(Item $item, string $position): bool
    {
        $this->getItems(false);
        if($this->items->contains($item)) {
            switch ($position) {
                case '-1':
                case 'up':
                    # code...
                    break;
                case '+1':
                case 'down':
                    // $this->items->removeElement($item);
                    // $items = $this->items->toArray();
                    // $this->items->clear();
                    // $cpt = 0;
                    // $next = null;
                    // foreach ($items as $it) {
                    //     if($next) {
                    //         $this->items->add($next);
                    //         $next = null;
                    //     }
                    //     if($it === $item) {
                    //         $next = $it;
                    //     } else {
                    //         $this->items->add($it);
                    //     }
                    //     $cpt++;
                    // }
                    break;
                case 'top':
                    $this->items->removeElement($item);
                    $items = $this->items->toArray();
                    $this->items->clear();
                    $this->items->add($item);
                    foreach ($items as $it) {
                        if($it !== $item) $this->items->add($it);
                    }
                    // $changed = true;
                    break;
                case 'bottom':
                    $this->items->removeElement($item);
                    $this->items->add($item);
                    // $changed = true;
                    break;
            }
        } else {
            throw new Exception(vsprintf('Error %s line %d: can not move "%s" because %s "%s" does not contain this %s "%s".', [__METHOD__, __LINE__, $position, $this->getShortname(), $this->__toString(), $item->getShortname(), $item->__toString()]));
        }
        return $this->updateRelationOrder();
    }

}
