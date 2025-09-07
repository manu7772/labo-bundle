<?php
namespace Aequation\LaboBundle\Model\Attribute;

use Aequation\LaboBundle\Model\Interface\AppAttributeConstantInterface;
use Aequation\LaboBundle\Model\Interface\AppAttributeMethodInterface;
use Aequation\LaboBundle\Model\Interface\AppAttributePropertyInterface;
use Aequation\LaboBundle\Model\Interface\CssClassInterface;
use Aequation\LaboBundle\Service\Tools\Iterables;
// PHP
use Attribute;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionClassConstant;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class CssClasses extends baseClassAttribute implements AppAttributeConstantInterface, AppAttributeMethodInterface, AppAttributePropertyInterface, CssClassInterface
{

    public const TARGET_VALUES = ['value','key'];

    public readonly ?ReflectionMethod $method;
    public readonly ?ReflectionProperty $property;
    public readonly ?ReflectionClassConstant $constant;

    public function __construct(
        public ?string $target = null,
    ) {
        if(empty($this->target)) $this->target = static::TARGET_VALUES[0];
        if(!in_array($this->target, static::TARGET_VALUES)) throw new Exception(vsprintf('target must set in these values : %s', [json_encode(static::TARGET_VALUES)]));
    }


    public function setConstant(ReflectionClassConstant|string $constant): static
    {
        $this->constant = is_string($constant) ? new ReflectionClassConstant($this->class->name, $constant) : $constant;
        $this->property = null;
        $this->method = null;
        return $this;
    }

    public function setMethod(ReflectionMethod|string $method): static
    {
        $this->method = is_string($method) ? new ReflectionMethod($this->class->name, $method) : $method;
        $this->property = null;
        $this->constant = null;
        if(!$this->method->isPublic()) throw new Exception(vsprintf('Error %s line %d: method "%s" of %s should be declared public.', [__METHOD__, __LINE__, $this->method->name, $this->class->name]));
        if($this->method->getNumberOfRequiredParameters() > 0) throw new Exception(vsprintf('Error %s line %d: method "%s" of %s should not require any required parameter (%d required).', [__METHOD__, __LINE__, $this->method->name, $this->class->name, $this->method->getNumberOfRequiredParameters()]));
        return $this;
    }

    public function setProperty(ReflectionProperty|string $property): static
    {
        $this->property = is_string($property) ? new ReflectionProperty($this->class->name, $property) : $property;
        $this->method = null;
        $this->constant = null;
        if(!$this->property->isPublic()) throw new Exception(vsprintf('Error %s line %d: property "%s" of %s should be declared public.', [__METHOD__, __LINE__, $this->property->name, $this->class->name]));
        if($this->property->isStatic()) throw new Exception(vsprintf('Error %s line %d: property "%s" of %s should be declared NON STATIC public.', [__METHOD__, __LINE__, $this->property->name, $this->class->name]));
        return $this;
    }

    public function getValue(): mixed
    {
        if($this->constant instanceof ReflectionClassConstant) {
            return $this->constant->getValue();
        }
        if($this->method instanceof ReflectionMethod) {
            $method_name = $this->method->name;
            $object = $this->getClassObject();
            if($object) {
                return $object->$method_name();
            } else if($this->method->isStatic()) {
                // no object found, but can use static method
                $class = $this->class->name;
                return $class::$method_name();
            } else {
                // no object found, method is not static, so can not get any method value
                throw new Exception(vsprintf('Error %s line %d: object of class %s not found and can not be instancied, so can not get value of method %s.', [__METHOD__, __LINE__, $this->class->name, $method_name]));
            }
        }
        if($this->property instanceof ReflectionProperty) {
            $property_name = $this->property->name;
            $object = $this->getClassObject();
            if($object) {
                return $object->$property_name;
            } else {
                // no object found, so can not get any property value
                throw new Exception(vsprintf('Error %s line %d: object of class %s not found and can not be instancied, so can not get value of property %s.', [__METHOD__, __LINE__, $this->class->name, $property_name]));
            }
        }
        return null;
    }

    public function getCssClasses(): array
    {
        $value = $this->getValue();
        $classes = [];
        switch (gettype($value)) {
            case 'array':
                $classes = $this->target === 'value' ? array_values($value) : array_keys($value);
                break;
            case 'string':
                if(!empty($value)) $classes = [$value];
                break;
        }
        $classes = Iterables::toClassList($classes, false);
        return $classes;
    }

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $data = [
            'target' => $this->target,
            'constant' => $this->constant ? $this->constant->name : null,
            'method' => $this->method ? $this->method->name : null,
            'property' => $this->property ? $this->property->name : null,
        ];
        return array_merge($parent, $data);
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->target = $data['target'];
        if(!empty($data['constant'])) {
            $this->setConstant($data['constant']);
        } else if(!empty($data['method'])) {
            $this->setMethod($data['method']);
        } else if(!empty($data['property'])) {
            $this->setProperty($data['property']);
        }
    }

}