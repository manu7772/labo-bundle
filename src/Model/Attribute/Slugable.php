<?php
namespace Aequation\LaboBundle\Model\Attribute;

use Aequation\LaboBundle\Model\Interface\AppAttributeClassInterface;
use Attribute;
use ReflectionProperty;

/**
 * Methods before Validate entity
 * @author emmanuel:dujardin Aequation
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Slugable extends baseClassAttribute implements AppAttributeClassInterface
{

    public ReflectionProperty|string $property;

    public function __construct(
        string $property,
    ) {
        $this->property = $property;
    }

    public function setClass(object $class): static
    {
        parent::setClass($class);
        if(is_string($this->property)) {
            $this->setProperty($this->property);
        }
        return $this;
    }

    public function setProperty(ReflectionProperty|string $property): static
    {
        $this->property = isset($this->class) && is_string($property)
            ? new ReflectionProperty($this->class->name, $property)
            : $property;
        return $this;
    }

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $data = [
            'property' => is_string($this->property) ? $this->property : $this->property->name,
        ];
        return array_merge($parent, $data);
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->property = $this->setProperty($data['property']);
    }

}