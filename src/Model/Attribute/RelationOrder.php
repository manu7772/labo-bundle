<?php
namespace Aequation\LaboBundle\Model\Attribute;

use Aequation\LaboBundle\Model\Interface\AppAttributePropertyInterface;
use Attribute;
use ReflectionClass;
use ReflectionProperty;

/**
 * Methods before Validate entity
 * @Target({"METHOD"})
 * @author emmanuel:dujardin Aequation
 */
#[Attribute(groups: Attribute::TARGET_PROPERTY)]
class RelationOrder extends baseClassAttribute implements AppAttributePropertyInterface
{

    public readonly ReflectionProperty $property;

    public function setProperty(ReflectionProperty|string $property): static
    {
        $this->property = is_string($property) ? new ReflectionProperty($this->class->name, $property) : $property;
        return $this;
    }

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $data = [
            'property' => $this->property ? $this->property->name : null,
        ];
        return array_merge($parent, $data);
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->setProperty($data['property']);
    }

}