<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Interface\ServiceInterface;

use Attribute;
use Exception;
use ReflectionClass;
use ReflectionAttribute;
use ReflectionMethod;

class ClassDescriptor extends ReflectionClass implements ServiceInterface
{

    protected array $attributes;
    protected ClassDescriptor|false $parentClass;
    protected array $parentClasses;
    public readonly ?LaboReflectionAttribute $laboReflectionAttribute;

    public function __construct(
        $objectOrClass
    )
    {
        if($objectOrClass instanceof ClassDescriptor) {
            throw new Exception(vsprintf('Error %s line %d: please try not to create another same ClassDescriptor to save some memory!', [__METHOD__, __LINE__]));
        }
        if($objectOrClass instanceof ReflectionAttribute) {
            // Attribute
            $this->laboReflectionAttribute = new LaboReflectionAttribute($objectOrClass);
            $objectOrClass = $this->laboReflectionAttribute->getName();
        }
        if($objectOrClass instanceof ReflectionClass) {
            $objectOrClass = $objectOrClass->name;
        }
        // construct
        parent::__construct($objectOrClass);
        // Check if is Attribute
        if(!isset($this->laboReflectionAttribute)) {
            $attributes = parent::getAttributes(Attribute::class, 0);
            if(count($attributes) > 0) {
                $this->laboReflectionAttribute = new LaboReflectionAttribute(reset($attributes));
                // if(!$this->isAttribute()) throw new Exception(vsprintf('Error %s line %d: found %d Attribute attribute(s) to this entity %s but is not so!', [__METHOD__, __LINE__, count($attributes), $this->name]));
            } else {
                // if($this->isAttribute()) throw new Exception(vsprintf('Error %s line %d: found NO Attribute attribute to this entity %s but is so!', [__METHOD__, __LINE__, $this->name]));
                $this->laboReflectionAttribute = null;
            }
        }
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'classname' => $this->name,
            'namespace' => $this->getNamespaceName(),
            'type' => $this->getClassTypeName(),
            'class' => $this->isClass(),
            'attribute' => $this->isAttribute(),
            'interface' => $this->isInterface(),
            'trait' => $this->isTrait(),
            'enum' => $this->isEnum(),
        ];
    }

    public function isValid(): bool
    {
        $errors = $this->laboReflectionAttribute->getErrors();
        return empty($errors);
    }

    public function getErrors(): array
    {
        return $this->laboReflectionAttribute instanceof LaboReflectionAttribute
            ? $this->laboReflectionAttribute->getErrors()
            : [];
    }


    /******************************************************************************************************/
    /** CLASS TYPE                                                                                        */
    /******************************************************************************************************/

    /**
     * Is a class?
     * @return boolean
     */
    public function isClass(): bool
    {
        return !$this->isInterface()
            && !$this->isTrait()
            && !$this->isEnum();
    }

    /**
     * Get main type
     * @return string
     */
    public function getClassTypeName(): string
    {
        $types = iterator_to_array($this->getClassTypeNames());
        return reset($types);
        // /** alternative method */
        // $types = $this->getClassTypeNames();
        // $types->rewind();
        // return $types->current();
    }

    /**
     * Get types
     * @return iterable
     */
    public function getClassTypeNames(): iterable
    {
        if($this->isAttribute()) yield 'attribute';
        if($this->isInterface()) yield 'interface';
        if($this->isTrait()) yield 'trait';
        if($this->isEnum()) yield 'enum';
        if($this->isClass()) yield 'class';
    }


    /******************************************************************************************************/
    /** TRAITS                                                                                            */
    /******************************************************************************************************/

    public function getTraits(): array
    {
        $traits = [];
        // $traits = parent::getTraits();
        // array_walk($traits, fn ($trait) => new static($trait));
        foreach (parent::getTraits() as $trait) {
            $traits[$trait->name] = new static($trait);
        }
        return $traits;
    }

    /**
     * Get all parent's Traits
     * @return array
     */
    public function getParentsTraits(): array
    {
        $traits = [];
        foreach ($this->getParentClasses() as $name => $parent) {
            foreach ($parent->getTraits() as $trait_name => $trait) {
                $traits[$trait_name] = $trait;
            }
        }
        return $traits;
    }

    /**
     * Get Traits with parent Traits
     * @return array
     */
    public function getAllTraits(): array
    {
        $traits = $this->getTraits();
        foreach ($this->getParentsTraits() as $name => $trait) {
            $traits[$name] = $trait;
        }

        return $traits;
    }

    public function hasTrait(
        string $classname,
        bool $search_in_parents = true
    ): bool
    {
        $traits = $search_in_parents ? $this->getAllTraits() : $this->getTraits();
        return array_key_exists($classname, $traits);
    }

    public function isTraitInParent(
        string|ClassDescriptor $classname
    ): bool
    {
        if($classname instanceof ClassDescriptor) $classname = $classname->name;
        if($this->hasTrait($classname, false)) return false;
        if($this->hasTrait($classname, true)) return true;
        return false;
    }

    public function getTraitOwner(
        string|ClassDescriptor $classname
    ): ?string
    {
        if($classname instanceof ClassDescriptor) $classname = $classname->name;
        $owneds = $this->getTraits();
        if(array_key_exists($classname, $owneds)) return $this->name;
        foreach ($this->getParentClasses() as $parent) {
            $owneds = $parent->getTraits();
            if(array_key_exists($classname, $owneds)) return $parent->name;
        }
        return null;
    }

    /******************************************************************************************************/
    /** OVERRIDE: PARENTS                                                                                 */
    /******************************************************************************************************/

    public function hasParent(): bool
    {
        return $this->getParentClass() instanceof static;
    }

    public function getParentClass(): static|false
    {
        if(!isset($this->parentClass)) {
            $parentClass = parent::getParentClass();
            $this->parentClass = $parentClass
                ? new static($parentClass)
                : false;
        }
        return $this->parentClass;
    }

    public function getParentClasses(): array
    {
        if(!isset($this->parentClasses)) {
            $this->parentClasses = [];
            $parent = $this;
            while ($parent = $parent->getParentClass()) {
                $this->parentClasses[$parent->name] = $parent;
            }
        }
        return $this->parentClasses;
    }

    /******************************************************************************************************/
    /** INTERFACES                                                                                        */
    /******************************************************************************************************/

    public function getInterfaces(): array
    {
        $interfaces = [];
        foreach (parent::getInterfaces() as $interface) {
            $interfaces[$interface->name] = new static($interface);
        }
        return $interfaces;
    }

    /******************************************************************************************************/
    /** ATTRIBUTES                                                                                        */
    /******************************************************************************************************/

    public function getAttributes(
        ?string $name = null,
        int $flags = 0
    ): array
    {
        $attributes = [];
        foreach (parent::getAttributes($name, $flags) as $attribute) {
            $attributes[$attribute->getName()] = new static($attribute);
        }
        return $attributes;
    }

    public function isAttribute(): bool
    {
        return $this->hasAttribute(Attribute::class, 0);
    }

    public function hasAttribute(
        string $attrClass = null,
        ?int $flags = null
    ): bool
    {
        if(empty($attrClass)) return !empty($this->getAttributes());
        if(!is_int($flags)) $flags = ReflectionAttribute::IS_INSTANCEOF;
        $attributes = $this->getAttributes($attrClass, $flags);
        return !empty($attributes);
    }

    /**
     * Find attributes
     * @param string $attrClass
     * @return array
     */
    public function getDeepAttributes(
        string $attrClass = null,
        ?int $flags = null
    ): array
    {
        $deepAttributes = [];
        if(!is_int($flags)) $flags = ReflectionAttribute::IS_INSTANCEOF;

        return $deepAttributes;
    }


    /******************************************************************************************************/
    /** ARCHITECTURE                                                                                      */
    /******************************************************************************************************/

    public function getClassAttributes(
        string $attrClass = null,
        int $targets = 0,
        bool $instanceOf = true
    ): array
    {
        $attributes = [];
        if (empty($targets) || ($targets & Attribute::TARGET_CLASS) === Attribute::TARGET_CLASS) {
            $instanceOf = $instanceOf ? ReflectionAttribute::IS_INSTANCEOF : 0;
            foreach ($this->getAttributes($attrClass, $instanceOf) as $attribute) {
                $all_attributes[$attribute->getName()] ??= [];
                $all_attributes[$attribute->getName()][] = $attribute;
            }
        }
        return $attributes;
    }

    public function getPropertiesAttributes(
        string $attrClass = null,
        int $targets = 0,
        bool $instanceOf = true
    ): array
    {
        $attributes = [];
        if (empty($targets) || ($targets & Attribute::TARGET_PROPERTY) === Attribute::TARGET_PROPERTY) {
            $instanceOf = $instanceOf ? ReflectionAttribute::IS_INSTANCEOF : 0;
            foreach ($this->getProperties() as $property) {
                foreach ($property->getAttributes($attrClass, $instanceOf) as $attribute) {
                    $cd = new static($attribute);
                    if(!$cd->isValid()) {
                        // throw new Exception(vsprintf('Error %s line %d: property %s::%s has attribute %s errors (%d) =>%s', [__METHOD__, __LINE__, $this->name, $property->name, $attribute->getName(), count($this->getErrors()), PHP_EOL.'- '.implode(PHP_EOL.'- ', $this->getErrors())]));
                    }
                    if($cd->isValid()) {
                        $attributes[$attribute->getName()] ??= [];
                        $attributes[$attribute->getName()][] = $cd;
                    }
                }
            }
        }
        return $attributes;
    }

    public function getMethodsAttributes(
        string $attrClass = null,
        int $targets = 0,
        bool $instanceOf = true
    ): array
    {
        $attributes = [];
        if (empty($targets) || ($targets & Attribute::TARGET_METHOD) === Attribute::TARGET_METHOD) {
            $instanceOf = $instanceOf ? ReflectionAttribute::IS_INSTANCEOF : 0;
            foreach ($this->getMethods() as $method) {
                foreach ($method->getAttributes($attrClass, $instanceOf) as $attribute) {
                    $cd = new static($attribute);
                    if(!$cd->isValid()) {
                        // throw new Exception(vsprintf('Error %s line %d: method %s::%s has attribute %s errors (%d) =>%s', [__METHOD__, __LINE__, $this->name, $method->name, $attribute->getName(), count($this->getErrors()), PHP_EOL.'- '.implode(PHP_EOL.'- ', $this->getErrors())]));
                    }
                    if($cd->isValid()) {
                        $attributes[$attribute->getName()] ??= [];
                        $attributes[$attribute->getName()][] = $cd;
                    }
                }
                // Get parameters of methods
                foreach ($this->getParametersAttributes($method, $attrClass, $targets, $instanceOf) as $name => $attrs) {
                    $attributes[$attribute->getName()] ??= [];
                    $attributes[$attribute->getName()][] = $cd;
                }
            }
        }
        return $attributes;
    }

    public function getParametersAttributes(
        ReflectionMethod|string $method_name,
        string $attrClass = null,
        int $targets = 0,
        bool $instanceOf = true
    ): array
    {
        $attributes = [];
        if (empty($targets) || ($targets & Attribute::TARGET_PARAMETER) === Attribute::TARGET_PARAMETER) {
            $instanceOf = $instanceOf ? ReflectionAttribute::IS_INSTANCEOF : 0;
            $method = $this->getMethod(is_string($method_name) ? $method_name : $method_name->name);
            if($method instanceof ReflectionMethod) {
                if (empty($targets) || ($targets & Attribute::TARGET_PARAMETER) === Attribute::TARGET_PARAMETER) {
                    foreach ($method->getParameters() as $parameter) {
                        foreach ($parameter->getAttributes($attrClass, $instanceOf) as $attribute) {
                            $cd = new static($attribute);
                            if(!$cd->isValid()) {
                                // throw new Exception(vsprintf('Error %s line %d: method %s::%s has attribute %s errors (%d) =>%s', [__METHOD__, __LINE__, $this->name, $method->name, $attribute->getName(), count($this->getErrors()), PHP_EOL.'- '.implode(PHP_EOL.'- ', $this->getErrors())]));
                            }
                            if($cd->isValid()) {
                                $attributes[$attribute->getName()] ??= [];
                                $attributes[$attribute->getName()][] = $cd;
                            }
                        }
                    }
                }
            } else {
                throw new Exception(vsprintf('Error %s line %d: method %s::%s not found!', [__METHOD__, __LINE__, $this->name, is_string($method_name) ? $method_name : $method_name->name]));
            }
        }
        return $attributes;
    }

    public function getReflectionConstantsAttributes(
        string $attrClass = null,
        int $targets = 0,
        bool $instanceOf = true
    ): array
    {
        $attributes = [];
        if (empty($targets) || ($targets & Attribute::TARGET_CLASS_CONSTANT) === Attribute::TARGET_CLASS_CONSTANT) {
            $instanceOf = $instanceOf ? ReflectionAttribute::IS_INSTANCEOF : 0;
            foreach ($this->getReflectionConstants() as $constant) {
                foreach ($constant->getAttributes($attrClass, $instanceOf) as $attribute) {
                    $cd = new static($attribute);
                    if(!$cd->isValid()) {
                        // throw new Exception(vsprintf('Error %s line %d: constant %s::%s has attribute %s errors (%d) =>%s', [__METHOD__, __LINE__, $this->name, $constant->name, $attribute->getName(), count($this->getErrors()), PHP_EOL.'- '.implode(PHP_EOL.'- ', $this->getErrors())]));
                    }
                    if($cd->isValid()) {
                        $attributes[$attribute->getName()] ??= [];
                        $attributes[$attribute->getName()][] = $cd;
                    }
                }
            }
        }
        return $attributes;
    }

    public function getAllAttributes(
        string $attrClass = null,
        int $targets = 0,
        bool $instanceOf = true
    ): array
    {
        $all_attributes = [];
        // Class
        if (empty($targets) || ($targets & Attribute::TARGET_CLASS) === Attribute::TARGET_CLASS) {
            foreach ($this->getAttributes($attrClass, $instanceOf ? ReflectionAttribute::IS_INSTANCEOF : 0) as $attribute) {
                $all_attributes[$attribute->getName()] ??= [];
                $all_attributes[$attribute->getName()][] = $attribute;
            }
        }
        // Property
        if (empty($targets) || ($targets & Attribute::TARGET_PROPERTY) === Attribute::TARGET_PROPERTY) {
            foreach ($this->getPropertiesAttributes($attrClass, $instanceOf) as $name => $attributes) {
                $all_attributes[$name] = array_merge($all_attributes[$name] ?? [], $attributes);
            }
        }
        // Method
        if (empty($targets) || ($targets & Attribute::TARGET_METHOD) === Attribute::TARGET_METHOD) {
            foreach ($this->getMethodsAttributes($attrClass, $instanceOf) as $name => $attributes) {
                $all_attributes[$name] = array_merge($all_attributes[$name] ?? [], $attributes);
            }
        }
        // Constant
        if (empty($targets) || ($targets & Attribute::TARGET_CLASS_CONSTANT) === Attribute::TARGET_CLASS_CONSTANT) {
            foreach ($this->getReflectionConstantsAttributes($attrClass, $instanceOf) as $name => $attributes) {
                $all_attributes[$name] = array_merge($all_attributes[$name] ?? [], $attributes);
            }
        }
        return $all_attributes;
    }

    public function getAllAttributesClassed(
        string $attrClass = null,
        int $targets = 0,
        bool $instanceOf = true
    ): array
    {
        $attributes = [
            'class' => [],
            'property' => [],
            'method' => [],
            'constant' => [],
        ];
        $instanceOf = $instanceOf ? ReflectionAttribute::IS_INSTANCEOF : 0;
        // Class attributes
        if (empty($targets) || ($targets & Attribute::TARGET_CLASS) === Attribute::TARGET_CLASS) {
            foreach ($this->getAttributes($attrClass, $instanceOf) as $attribute) {
                $attributes['class'][$attribute->getName()] ??= [];
                $attributes['class'][$attribute->getName()][] = $attribute;
            }
        }
        // Property attributes
        if (empty($targets) || ($targets & Attribute::TARGET_PROPERTY) === Attribute::TARGET_PROPERTY) {
            foreach ($this->getProperties() as $property) {
                foreach ($property->getAttributes($attrClass, $instanceOf) as $attribute) {
                    $attributes['property'][$property->name][$attribute->getName()] ??= [];
                    $attributes['property'][$property->name][$attribute->getName()][] = $attribute;
                }
            }
        }
        // Method attributes / Parameter attributes
        if (empty($targets) || ($targets & Attribute::TARGET_METHOD) === Attribute::TARGET_METHOD || ($targets & Attribute::TARGET_PARAMETER) === Attribute::TARGET_PARAMETER) {
            foreach ($this->getMethods() as $method) {
                foreach ($method->getAttributes($attrClass, $instanceOf) as $attribute) {
                    $attributes['method'][$method->name][$attribute->getName()] ??= [];
                    $method_attr_data = [
                        'attribute' => $attribute,
                        'parameters' => [],
                    ];
                    // Parameters attributes
                    if (empty($targets) || ($targets & Attribute::TARGET_PARAMETER) === Attribute::TARGET_PARAMETER) {
                        foreach ($method->getParameters() as $parameter) {
                            foreach ($parameter->getAttributes($attrClass, $instanceOf) as $attribute) {
                                $method_attr_data['parameters'][$parameter->name][$attribute->getName()] ??= [];
                                $method_attr_data['parameters'][$parameter->name][$attribute->getName()][] = $attribute;
                            }
                        }
                    }
                    $attributes['method'][$method->name][$attribute->getName()][] = $method_attr_data;
                }
            }
        }
        // Constant attributes
        if (empty($targets) || ($targets & Attribute::TARGET_CLASS_CONSTANT) === Attribute::TARGET_CLASS_CONSTANT) {
            foreach ($this->getReflectionConstants() as $constant) {
                foreach ($constant->getAttributes($attrClass, $instanceOf) as $attribute) {
                    $attributes['constant'][$constant->name][$attribute->getName()] ??= [];
                    $attributes['constant'][$constant->name][$attribute->getName()][] = $attribute;
                }
            }
        }
        return $attributes;
    }


    /******************************************************************************************************/
    /** STATIC TOOLS                                                                                      */
    /******************************************************************************************************/

    public static function filterClassesList(
        array &$list_of_classes
    ): void
    {
        /**
         * STOPPED:
         * 127.0.0.1:46332 [500]: GET /php - Declaration of Symfony\Bridge\Doctrine\Middleware\Debug\DBAL3\Connection::beginTransaction(): bool must be compatible with Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware::beginTransaction(): void in /home/edujardin/Projects/_demo/vendor/symfony/doctrine-bridge/Middleware/Debug/DBAL3/Connection.php on line 80
         */
        // $list_of_classes = array_filter($list_of_classes, function($class) {
        //     try {
        //         return class_exists($class) || interface_exists($class) || trait_exists($class);
        //     } catch (\Throwable $th) {
        //         return false;
        //     }
        // });
    }

    public static function getFilteredUserAllClasses(
        array $list_of_classes,
        string $regex_filter = null,
        bool $filter_user_defined = true,
    ): array
    {
        static::filterClassesList($list_of_classes);
        $classes = [];
        foreach ($list_of_classes as $class) {
            if(empty($regex_filter) || preg_match($regex_filter, $class)) {
                $class = new static($class);
                if((!$filter_user_defined || $class->isUserDefined())) {
                    $classes[$class->getClassTypeName()][$class->name] = $class;
                }
            }
        }
        ksort($classes);
        return $classes;
    }

    public static function getFilteredUserClasses(
        array $list_of_classes,
        string $regex_filter = null,
        bool $filter_user_defined = true,
    ): array
    {
        static::filterClassesList($list_of_classes);
        $classes = [];
        foreach ($list_of_classes as $class) {
            if(empty($regex_filter) || preg_match($regex_filter, $class)) {
                $class = new static($class);
                if((!$filter_user_defined || $class->isUserDefined()) && $class->isClass()) {
                    $classes[$class->name] = $class;
                }
            }
        }
        return $classes;
    }

    public static function getFilteredUserAttributes(
        array $list_of_classes,
        string $regex_filter = null,
        bool $filter_user_defined = true,
    ): array
    {
        static::filterClassesList($list_of_classes);
        $attributes = [];
        foreach ($list_of_classes as $class) {
            if(empty($regex_filter) || preg_match($regex_filter, $class)) {
                $class = new static($class);
                if((!$filter_user_defined || $class->isUserDefined()) && $class->isAttribute()) {
                    $attributes[$class->name] = $class;
                }
            }
        }
        return $attributes;
    }

    public static function getFilteredUserInterfaces(
        array $list_of_classes,
        string $regex_filter = null,
        bool $filter_user_defined = true,
    ): array
    {
        static::filterClassesList($list_of_classes);
        $interfaces = [];
        foreach ($list_of_classes as $interface) {
            if(empty($regex_filter) || preg_match($regex_filter, $interface)) {
                $interface = new static($interface);
                if((!$filter_user_defined || $interface->isUserDefined()) && $interface->isInterface()) {
                    $interfaces[$interface->name] = $interface;
                }
            }
        }
        return $interfaces;
    }

    public static function getFilteredUserTraits(
        array $list_of_classes,
        string $regex_filter = null,
        bool $filter_user_defined = true,
    ): array
    {
        static::filterClassesList($list_of_classes);
        $traits = [];
        foreach ($list_of_classes as $trait) {
            if(empty($regex_filter) || preg_match($regex_filter, $trait)) {
                $trait = new static($trait);
                if((!$filter_user_defined || $trait->isUserDefined()) && $trait->isTrait()) {
                    $traits[$trait->name] = $trait;
                }
            }
        }
        return $traits;
    }

}
