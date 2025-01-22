<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Component\AppEntityInfo;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\OwnerInterface;
use Aequation\LaboBundle\Model\Trait\AppEntity;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Exception;
use Throwable;

#[MappedSuperclass]
abstract class MappSuperClassEntity implements AppEntityInterface
{
    use AppEntity;

    public const SERIALIZATION_PROPS = ['id'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(['index'])]
    private ?int $id = null;

    public function __construct()
    {
        $this->__construct_entity();
    }

    public function __clone()
    {
        $this->_service->dispatchEvent($this, AppEvent::beforeClone);
        $this->_setClone(true);
        $this->id = null;
        $this->__clone_entity(); // ----> UPDATE $this->_appManaged;
        if($this instanceof OwnerInterface) {
            $this->_service->defineEntityOwner($this, true);
        }
        $this->_setClone(false);
        $this->_service->dispatchEvent($this, AppEvent::afterClone);
        if($this->_service->isDev() && $this->_appManaged->entity !== $this) {
            throw new Exception(vsprintf('Error %s line %d: this %s "%s" (id:%s) owned %s is invalid (has other entity %s "%s" - id:%s)!', [__METHOD__, __LINE__, $this->getClassname(), $this, $this->getId() ?? 'null', AppEntityInfo::class, $this->_appManaged->entity->getClassname(), $this->_appManaged->entity, $this->_appManaged->entity->getId() ?? 'null']));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Serializer\Ignore]
    public function getSelf(): static
    {
        return $this;
    }

    #[Serializer\Ignore]
    public function __toString(): string
    {
        $id = $this->_appManaged->isNew() ? '' : '@'.$this->getId();
        return $this->getShortname().$id;
    }

    public function __serialize(): array
    {
        $array = ['id' => $this->id];
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        foreach (static::SERIALIZATION_PROPS as $attr) {
            $array[$attr] = $accessor->getValue($this, $attr);
        }
        return $array;
    }

    public function __unserialize(array $data): void
    {
        $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        foreach ($data as $attr => $value) {
            try {
                $accessor->setValue($this, $attr, $value);
            } catch (Throwable $th) {
                $this->$attr = $value;
            }
        }
    }

    public function serialize(): ?string
    {
        $array = $this->__serialize();
        return json_encode($array);
    }

    public function unserialize(string $data): void
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

}