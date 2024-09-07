<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Component\Opresult;
use Aequation\LaboBundle\Model\Interface\AppAttributeClassInterface;
use Aequation\LaboBundle\Model\Interface\AppAttributeConstantInterface;
use Aequation\LaboBundle\Model\Interface\AppAttributeMethodInterface;
use Aequation\LaboBundle\Model\Interface\AppAttributePropertyInterface;
use Aequation\LaboBundle\Service\Base\BaseService;

use function Symfony\Component\String\u;

use Exception;
use ReflectionClass;
use ReflectionAttribute;
use ReflectionClassConstant;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

class Classes extends BaseService
{

    /*************************************************************************************
     * TYPES FOR PROPERTY/METHOD
     *************************************************************************************/

    /**
     * Get list of required types of property or method
     * @param ReflectionType|ReflectionParameter $reflectionType
     * @return array
     */
    public static function findTypes(
        ReflectionType|ReflectionParameter $reflectionType
    ): array
    {
        $types = [];
        switch (true) {
            case $reflectionType instanceof ReflectionParameter:
                if($reflectionType->allowsNull()) $types[] = 'null';
                $types = array_merge($types, static::findTypes($reflectionType->getType()));
                break;
            case $reflectionType instanceof ReflectionUnionType:
                if($reflectionType->allowsNull()) $types[] = 'null';
                foreach ($reflectionType->getTypes() as $type) {
                    $types = array_merge($types, static::findTypes($type));
                }
                break;
            case $reflectionType instanceof ReflectionIntersectionType:
                if($reflectionType->allowsNull()) $types[] = 'null';
                foreach ($reflectionType->getTypes() as $type) {
                    $types = array_merge($types, static::findTypes($type));
                }
                break;
            case $reflectionType instanceof ReflectionNamedType:
                if($reflectionType->allowsNull()) $types[] = 'null';
                $types[] = $reflectionType->getName();
                break;
            default:
                throw new Exception(vsprintf('Error %s line %d: not supported yet %s', [__METHOD__, __LINE__, get_class($reflectionType)]));
                break;
        }
        return array_unique($types);
    }

    /**
     * Get description of property or method ($name) of object
     * @param object $object
     * @param string $name
     * @return Opresult
     */
    public static function getRequiredTypes(
        object $object,
        string $name,
    ): Opresult
    {
        $opresult = new Opresult();
        $data = [];
        $rc = new ReflectionClass($object);
        // Test property
        if($rc->hasProperty($name)) {
            $property = $rc->getProperty($name);
            if($property->isPublic()) {
                $data['access'] = 'property';
                $data['property'] = $property;
                $data['name'] = $name;
                $data['types'] = static::findTypes($property->getType());
                $data['class_types'] = [];
                foreach ($data['types'] as $type) {
                    if(class_exists($type)) $data['class_types'][] = $type;
                }
            }
        }
        if(empty($data)) {
            // Test Methods
            $setters = [$name, 'set'.ucfirst($name), 'add'.ucfirst($name)];
            $method = null;
            foreach ($setters as $setter) {
                // $setter = $setter.ucfirst($name);
                if($rc->hasMethod($setter)) {
                    $method = $rc->getMethod($setter);
                    if($method->isPublic() && $method->getNumberOfParameters() > 0 && $method->getNumberOfRequiredParameters() < 2) {
                        $parameters = $method->getParameters();
                        $data['access'] = 'method';
                        $data['method'] = $method;
                        $data['name'] = $setter;
                        $data['types'] = static::findTypes(reset($parameters));
                        $data['class_types'] = [];
                        foreach ($data['types'] as $type) {
                            if(class_exists($type)) $data['class_types'][] = $type;
                        }
                        break;
                    }
                }
            }
        }
        if(empty($data)) {
            return $opresult->addDanger(vsprintf('Error %s line %d: no method nore property access named %s found for %s', [__METHOD__, __LINE__, $name, get_class($object)]));
        }
        $opresult->setData($data);
        $opresult->addSuccess(vsprintf('Found %d types for %s property of %s', [count($data['types']), $name, get_class($object)]));
        return $opresult;
    }

    /**
     * Change type of the $value by required type of property or method ($name) of object
     * @param object $object
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public static function transtype(
        object $object,
        string $name,
        mixed $value
    ): mixed
    {
        $typesResult = static::getRequiredTypes($object, $name);
        // dd($typesResult);
        if($typesResult->isFail()) throw new Exception($typesResult->getMessagesAsString());
        $data = $typesResult->getData();
        // all types granteds
        if(in_array('null', $data['types']) || in_array('mixed', $data['types'])) return $value;
        // Same types
        if(!is_object($value) && in_array(gettype($value), $data['types'])) return $value;
        foreach ($data['class_types'] as $test_class) {
            if(is_a($value, $test_class)) return $value;
        }
        if(is_string($value)) {
            $value = new $test_class($value);
        }
        // dd(__LINE__, gettype($value), $value, $data);
        return $value;
    }


    /*************************************************************************************
     * METHODS
     *************************************************************************************/

    public static function toGetter(
        string $property
    ): string
    {
        return u('get_'.$property)->camel();
    }

    public static function toSetter(
        string $property
    ): string
    {
        return u('set_'.$property)->camel();
    }

    public static function toIser(
        string $property
    ): string
    {
        return u('is_'.$property)->camel();
    }

    public static function toHaser(
        string $property
    ): string
    {
        return u('has_'.$property)->camel();
    }


    /*************************************************************************************
     * CLASSES
     *************************************************************************************/

    /**
     * Retrieve class or parent class that contains the DECLARED property
     * @param object|string $class
     * @param string $propertyName
     * @return ReflectionClass|false
     */
    public static function getClassDeclaratorOfProperty(
        object|string $class,
        string $propertyName
    ): ReflectionClass|false
    {
        $reflClass = new ReflectionClass($class);
        while (!$reflClass->hasProperty($propertyName)) {
            $parent = $reflClass->getParentClass();
            if(!$parent) return false;
            $reflClass = $parent;
        }
        return $reflClass;
    }

    /**
     * Get classname of class
     * @param object|string $objectOrClass
     * @return string|null
     */
    public static function getClassname(
        object|string $objectOrClass,
    ): ?string
    {
        if(is_object($objectOrClass)) return get_class($objectOrClass);
        if(!class_exists($objectOrClass)) return null;
        $rc = new ReflectionClass($objectOrClass);
        return $rc->name;
    }

    /**
     * Get shortname (without namespace) of class
     * @param object|string $objectOrClass
     * @param boolean $getfast
     * @return string|null
     */
    public static function getShortname(
        object|string $objectOrClass,
        // bool $getfast = false
    ): ?string
    {
        // if($getfast) {
        //     if(is_object($objectOrClass)) $objectOrClass = get_class($objectOrClass);
        //     return u($objectOrClass)->afterLast('\\');
        // }
        // if(!class_exists($objectOrClass) && !interface_exists($objectOrClass) && !trait_exists($objectOrClass)) return null;
        try {
            $rc = new ReflectionClass($objectOrClass);
            return $rc->getShortName();
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Get parent classes of class
     * @param object|string $objectOrClass
     * @param boolean $reverse
     * @return array
     */
    public static function getParentClasses(
        object|string $objectOrClass,
        bool $reverse = false,
        bool $asReflclass = true,
    ): array
    {
        if(is_string($objectOrClass) && !class_exists($objectOrClass)) return [];
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $parents = [];
        while ($reflClass = $reflClass->getParentClass()) {
            $parents[] = $reflClass;
        }
        if(!$asReflclass) {
            foreach ($parents as $key => $parent) {
                $parents[$key] = $parent->name;
            }
        }
        return $reverse ? array_reverse($parents) : $parents;
    }

    /**
     * Get classes of interface
     * @param string|array $interfaces
     * @param array|null $listOfClasses
     * @return array
     */
    public static function filterByInterface(
        string|array $interfaces,
        ?array $listOfClasses = null,
    ): array
    {
        $interfaces = (array)$interfaces;
        if(empty($interfaces)) return [];
        if(empty($listOfClasses)) $listOfClasses = get_declared_classes();
        return array_filter($listOfClasses, function ($classname) use ($interfaces) {
            foreach ($interfaces as $interface) {
                if(is_a($classname, $interface, true)) return true;
            }
            return false;
        });
    }

    /**
     * Get all children classes of a class
     * @param object|string $objectOrClass
     * @param boolean $reverse
     * @param array|null $listOfClasses
     * @return array
     */
    public static function getInheritedClasses(
        object|string $objectOrClass,
        bool $reverse = false,
        ?array $listOfClasses = null,
        bool $onlyInstantiables = false,
    ): array
    {
        if(is_object($objectOrClass)) $objectOrClass = get_class($objectOrClass);
        if(!class_exists($objectOrClass)) return [];
        $children = [];
        if(empty($listOfClasses)) $listOfClasses = get_declared_classes();
        foreach($listOfClasses as $class) {
            $RC = new ReflectionClass($class);
            $do = $onlyInstantiables
                ? $RC->isInstantiable()
                : true;
            if($do && is_subclass_of($class, $objectOrClass, true)) {
                $children[] = $RC;
            }
        }
        return $reverse ? array_reverse($children) : $children;
    }

    public static function getConstants(object|string $classOrObject): array
    {
        $rc = new ReflectionClass($classOrObject);
        return $rc->getConstants();
    }

    /**
     * Get attributes of $listOfClasses (or all classes if empty)
     * $listOfClasses can be:
     * - empty : get all declared classes
     * - class/object or array of class/object
     * - regular expression to filter all declared classes
     * @param string $attribute
     * @param array|object|string|null $listOfClasses
     * @return array
     */
    public static function getAttributes(
        string $attributeClass,
        array|object|string $listOfClasses = null,
    ): array
    {
        $attributes = [];
        if(empty($listOfClasses)) $listOfClasses = get_declared_classes();
        if(is_string($listOfClasses) && !class_exists($listOfClasses)) {
            // filter with REGEX
            $regex = $listOfClasses;
            $listOfClasses = [];
            foreach (get_declared_classes() as $class) {
                if(preg_match($regex, $class)) $listOfClasses[] = $class;
            }
        }
        if(!is_array($listOfClasses)) $listOfClasses = [$listOfClasses];

        foreach ($listOfClasses as $objectOrClass) {
            foreach (static::getClassAttributes($objectOrClass, $attributeClass, true) as $attrs) {
                foreach ((array)$attrs as $attr) $attributes[] = $attr;
            }
            foreach (static::getPropertysAttributes($objectOrClass, $attributeClass, true) as $attrs) {
                foreach ((array)$attrs as $attr) $attributes[] = $attr;
            }
            foreach (static::getMethodsAttributes($objectOrClass, $attributeClass, true) as $attrs) {
                foreach ((array)$attrs as $attr) $attributes[] = $attr;
            }
            foreach (static::getConstantAttributes($objectOrClass, $attributeClass, true) as $attrs) {
                foreach ((array)$attrs as $attr) $attributes[] = $attr;
            }
        }
        // dump($attributes);
        return $attributes;
    }

    public static function getClassAttributes(
        object|string $objectOrClass,
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $attributes = $reflClass->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);
        foreach ($attributes as $key => $attr) {
            $attributes[$key] = $attr = $attr->newInstance();
            if($attr instanceof AppAttributeClassInterface) $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
        }
        if(empty($attributes) && $searchParents) {
            // Try find in parent class (recursively)
            $parent = $reflClass->getParentClass();
            if($parent) return static::getClassAttributes($parent, $attributeClass, true);
        }
        return $attributes;
    }

    public static function getPropertysAttributes(
        object|string $objectOrClass,
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $propertys = $reflClass->getProperties();
        $attributes = [];
        foreach ($propertys as $property) {
            foreach ($property->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $attr = $attr->newInstance();
                if($attr instanceof AppAttributePropertyInterface) {
                    $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
                    $attr->setProperty($property);
                }
                $attributes[$property->name] ??= [];
                $attributes[$property->name][] = $attr;
            }
        }
        if($searchParents) {
            // Try find in parent class (recursively)
            if($parent = $reflClass->getParentClass()) {
                foreach (static::getPropertysAttributes($parent, $attributeClass, true) as $attrname => $attr) {
                    $attributes[$attrname] ??= $attr;
                }
            }
        }
        return $attributes;
    }

    public static function getMethodsAttributes(
        object|string $objectOrClass,
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $methods = $reflClass->getMethods();
        $attributes = [];
        foreach ($methods as $method) {
            foreach ($method->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $attr = $attr->newInstance();
                if($attr instanceof AppAttributeMethodInterface) {
                    $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
                    $attr->setMethod($method);
                }
                $attributes[$method->name] ??= [];
                $attributes[$method->name][] = $attr;
            }
        }
        if($searchParents) {
            // Try find in parent class (recursively)
            if($parent = $reflClass->getParentClass()) {
                foreach (static::getMethodsAttributes($parent, $attributeClass, true) as $attrname => $attr) {
                    $attributes[$attrname] ??= $attr;
                }
            }
        }
        return $attributes;
    }

    public static function getConstantAttributes(
        object|string $objectOrClass,
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        if($objectOrClass instanceof ReflectionClass) {
            $reflClass = $objectOrClass;
            $objectOrClass = $reflClass->name;
        } else {
            $reflClass = new ReflectionClass($objectOrClass);
        }
        $constants = $reflClass->getConstants();
        $attributes = [];
        foreach ($constants as $name => $value) {
            $reflClassConstant = new ReflectionClassConstant($objectOrClass, $name);
            foreach ($reflClassConstant->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $attr = $attr->newInstance();
                if($attr instanceof AppAttributeConstantInterface) {
                    // $attr->setConstant($constant);
                    $attr->setClass(is_object($objectOrClass) ? $objectOrClass : $reflClass);
                    $attr->setConstant($reflClassConstant);
                    // $attr->setValue($value);
                }
                $attributes[$reflClassConstant->name] ??= [];
                $attributes[$reflClassConstant->name][] = $attr;
            }
        }
        if($searchParents) {
            // Try find in parent class (recursively)
            if($parent = $reflClass->getParentClass()) {
                foreach (static::getConstantAttributes($parent, $attributeClass, true) as $attrname => $attr) {
                    $attributes[$attrname] ??= $attr;
                }
            }
        }
        return $attributes;
    }


}