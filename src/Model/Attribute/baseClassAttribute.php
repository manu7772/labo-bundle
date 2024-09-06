<?php
namespace Aequation\LaboBundle\Model\Attribute;

use ReflectionClass;
use Exception;
use Serializable;

abstract class baseClassAttribute implements Serializable
{

    public readonly ReflectionClass $class;
    public readonly object $object;

    public function getClassObject(): ?object
    {
        try {
            $class = $this->class->name;
            $this->object ??= new $class();
        } catch (\Throwable $th) {
            // throw new Exception(vsprintf('Error %s line %d: object of class %s is not defined and can not be instancied.', [__METHOD__, __LINE__, $this->class->name]));
        }
        return $this->object ?? null;
    }

    public function setClass(object $class): static
    {
        if(!($class instanceof ReflectionClass)) {
            $this->object = $class;
            $class = new ReflectionClass($class);
        }
        $this->class = $class;
        return $this;
    }

    public function __serialize(): array
    {
        return [
            'class' => $this->class->name,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->setClass(new ReflectionClass($data['class']));
    }

    public function serialize()
    {
        return json_encode($this->__serialize());
    }

    public function unserialize(string $data)
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

}