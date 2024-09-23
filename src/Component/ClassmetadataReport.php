<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\ClassmetadataReportInterface;
use Aequation\LaboBundle\Model\Attribute\ClassCustomService;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Strings;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Twig\Markup;

use Exception;
use ReflectionClass;

class ClassmetadataReport implements ClassmetadataReportInterface
{

    public readonly string $classname;
    protected ?AppEntityInterface $entity = null;
    public readonly ?AppEntityInterface $model;
    public readonly ClassMetadata $classMetadata;
    public readonly AppEntityManagerInterface $manager;
    public readonly string $managerID;
    public readonly array $phpChilds;
    protected bool $computed = false;
    protected array $uniqueFields;
    protected array $errors;

    public function __construct(
        public readonly AppEntityManagerInterface $appEntityManager,
        string $classname = null,
    )
    {
        if(is_string($classname)) {
            $this->setClassname($classname);
        }
    }


    /** SERIALIZABLE / JSON */

    // public function jsonSerialize(): mixed
    // {
    //     return $this->toArray();
    // }

    public function serialize(): ?string
    {
        return json_encode($this->toArray());
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function unserialize(string $data)
    {
        $data = json_decode($data, false);
        return new ClassmetadataReport($this->appEntityManager, $data['classname']);
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function toArray(): array
    {
        $data = [
            'classname' => $this->classname,
            'managerID' => $this->managerID,
            'phpChilds' => $this->phpChilds,
        ];
        return $data;
    }




    public function isValid(): bool
    {
        return isset($this->classname)
            && $this->appEntityManager->entityExists($this->classname, false, false)
            && isset($this->manager)
            && isset($this->managerID)
            && is_a($this->manager, $this->managerID)
            && isset($this->classMetadata);
    }

    public function setEntity(
        AppEntityInterface $entity
    ): static
    {
        if(!isset($this->classname)) {
            $this->setClassname($entity->getClassname());
            // throw new Exception(vsprintf('Error %s line %d: classname is not defined!', [__METHOD__, __LINE__]));
        }
        if($entity->getClassname() !== $this->classname) {
            throw new Exception(vsprintf('Error %s line %d: this entity classname "%s" is invalid, it should be a "%s"!', [__METHOD__, __LINE__, $entity->getClassname(), $this->classname]));
        }
        $this->entity = $entity;
        return $this;
    }

    public function getEntity(): ?AppEntityInterface
    {
        return $this->entity;
    }

    public function getModel(): ?AppEntityInterface
    {
        return $this->model;
    }

    public function setClassname(
        string $classname
    ): bool
    {
        if($this->appEntityManager->entityExists($classname, false, false)) {
            $this->classname = $classname;
            $this->classMetadata = $this->appEntityManager->getClassMetadata($this->classname);
            $this->manager = $this->appEntityManager->getEntityService($this->classMetadata->name) ?? $this->appEntityManager;
            $this->managerID = $this->appEntityManager::getEntityServiceID($this->classname);
            $this->model = $this->isInstantiable() ? $this->manager->getModel() : null;
        }
        return $this->isValid();
    }

    public function getData(): array|false
    {
        if($this->isValid()) {
            return [
                'classname' => $this->classname,
                'managerID' => $this->managerID,
                'phpChilds' => $this->phpChilds,
                'parent_classes' => $this->getParentClasses(),
            ];
        }
        return false;
    }

    public function __isset($name)
    {
        return method_exists($this, Classes::toGetter($name))
            || property_exists($this->classMetadata, $name);
    }

    public function __get($name)
    {
        $getter = Classes::toGetter($name);
        return method_exists($this, $getter)
            ? $this->$getter()
            : $this->classMetadata->$name;
    }

    public function __call($name, $arguments)
    {
        return $this->classMetadata->$name(...$arguments);
    }

    public function getMananger(): ?AppEntityManagerInterface
    {
        return $this->manager;
    }

    public function getManagerID(): ?string
    {
        return $this->managerID;
    }

    public function getShortname(): string
    {
        return $this->reflClass->getShortname();
    }

    public function getShortname_lower(): string
    {
        return strtolower($this->reflClass->getShortname());
    }

    public function getInterfaces(): array
    {
        return $this->classMetadata->reflClass->getInterfaces();
    }

    public function getConstants(): array
    {
        return $this->classMetadata->reflClass->getConstants();
    }

    public function getTraits(): array
    {
        return $this->classMetadata->reflClass->getTraits();
    }

    public function getAllTraits(
        bool $flatten = false
    ): array
    {
        if($flatten) {
            $traits = $this->getTraits();
            foreach ($this->getPhpParents() as $parent) {
                foreach ($parent->getTraits() as $trait) {
                    $traits[$trait->name] = $trait;
                }
            }
        } else {
            $traits = [
                $this->name => $this->getTraits(),
            ];
            foreach ($this->getPhpParents() as $parent) {
                $traits[$parent->name] = [];
                foreach ($parent->getTraits() as $trait) {
                    $traits[$parent->name][$trait->name] = $trait;
                }
            }
        }
        return $traits;
    }

    public function getBreadcrumbName(
        string $link = ' \\ ',
        bool $asHtml = true
    ): Markup
    {
        $name = [];
        foreach (array_reverse($this->getParentClasses()) as $parent) {
            $name[] = Classes::getShortname($parent->name);
        }
        $name[] = ($asHtml ? '<strong>' : '').$this->getShortname().($asHtml ? '</strong>' : '');
        return $asHtml
            ? Strings::markup(implode($link, $name))
            : implode($link, $name);
    }

    public function getPhpChilds(
        bool $onlyInstantiables = false
    ): array
    {
        return $this->phpChilds ??= Classes::getInheritedClasses($this->classMetadata->name, false, array_values($this->appEntityManager->getEntityNames(false)), $onlyInstantiables);
    }

    public function getParentReport(): ?ClassmetadataReport
    {
        $parents = $this->classMetadata->parentClasses;
        $parent = reset($parents);
        return empty($parent)
            ? null
            : $this->appEntityManager->getEntityMetadataReport($parent);
    }

    public function getChildrenReports(): iterable
    {
        foreach ($this->classMetadata->subClasses as $child) {
            if($child = $this->appEntityManager->getEntityMetadataReport($child)) {
                /** @var ClassmetadataReport $child */
                yield $child;
            }
        }
    }

    public function getParentClasses(
        bool $reverse = false
    ): array
    {
        $parents = [];
        foreach ($this->classMetadata->parentClasses as $parent) {
            $parents[] = new ReflectionClass($parent);
        }
        return $reverse
            ? array_reverse($parents)
            : $parents;
    }

    public function getPhpParents(
        bool $reverse = false
    ): array
    {
        return Classes::getParentClasses($this->classMetadata->name, $reverse, true);
    }

    public function getPhpRootParent(): ?string
    {
        $parents = $this->getPhpParents(true);
        return empty($parents) ? null : reset($parents);
    }

    public function isDoctrineRoot(): bool
    {
        return $this->rootEntityName === $this->classMetadata->name;
    }

    public function isPhpRoot(): bool
    {
        return empty($this->getPhpRootParent());
    }

    public function isAppEntity(): bool
    {
        return $this->manager::isAppEntity($this->classMetadata->name);
        // return is_a($this->classMetadata->name, AppEntityInterface::class, true);
    }

    public function isInstantiable(): bool
    {
        return $this->classMetadata->reflClass->isInstantiable();
    }

    public function getUniqueFields(
        bool $flatlisted = false
    ): array
    {
        $this->uniqueFields ??= $this->manager::getUniqueFields($this->classMetadata->name, null);
        return $this->uniqueFields[$flatlisted ? 'flatlist' : 'hierar'];
    }

    public function arraySortValue(
        ClassmetadataReport $otherReport
    ): int
    {
        if($otherReport === $this) return 0;
        $otherParents = count($otherReport->getPhpParents());
        $thisParents = count($this->getPhpParents());
        if($thisParents === $otherParents) {
            if($this->classMetadata->isMappedSuperclass) return -1;
            if($otherReport->classMetadata->isMappedSuperclass) return 1;
            if(!$this->isInstantiable() && $otherReport->isInstantiable()) return -1;
            if(!$otherReport->isInstantiable() && $this->isInstantiable()) return 1;
        }
        return $thisParents > $otherParents ? 1 : -1;
    }

    public static function sortReports(
        array &$reports
    ): void
    {
        usort($reports, function($a, $b) {
            return $a->arraySortValue($b);
        });
    }

    public function computeReport(): static
    {
        if(!$this->computed) {
            $this->errors = [];
            // HasLifecycleCallbacks & callbacks
            $callbacks = [];
            $nb_callbacks = 0;
            foreach ($this->lifecycleCallbacks as $name => $methods) {
                $callbacks[$name] = vsprintf('%s => %s', [$name, implode(', ', $methods)]);
                $nb_callbacks += count($methods);
            }
            $methodAttrs = [];
            foreach ($this->getPhpParents() as $class) {
                foreach ($class->getMethods() as $refmethod) {
                    foreach ($refmethod->getAttributes() as $refattr) {
                        if(preg_match(static::REGEX_ORM_MAPPING, $refattr->getName())) {
                            $methodAttrs[$refmethod->getName()] = vsprintf('%s => %s', [$refmethod->getName(), $refattr->getName()]);
                        }
                    }
                }
            }
            $hasLifecycleCallbacks = count($this->getClassAttributes(HasLifecycleCallbacks::class, true)) > 0;
            if(count($methodAttrs) > 0 && !$hasLifecycleCallbacks) {
                $this->errors[] = vsprintf('La propriété "HasLifecycleCallbacks" est manquante : cette entité %s contient %d méthodes avec callbacks (%s) qui ne seront pas appelées.', [$this->classMetadata->name, count($methodAttrs), implode(' / ', $methodAttrs)]);
            }
            if(count($methodAttrs) > $nb_callbacks) {
                $this->errors[] = vsprintf('Certains Callbacks ne sont pas trouvés par "HasLifecycleCallbacks" : pour cette entité %s, %d méthodes ont été trouvées, et seules %d seront prises en compte.', [$this->classMetadata->name, count($methodAttrs), $nb_callbacks]);
                dump($this->classMetadata->name, $methodAttrs);
            }
            if($nb_callbacks > 0 && !$hasLifecycleCallbacks) {
                $this->errors[] = vsprintf('La propriété "HasLifecycleCallbacks" est manquante : cette entité %s contient des callbacks (%s) qui ne seront pas appelés.', [$this->classMetadata->name, implode(' / ', $callbacks)]);
            }
            if($nb_callbacks <= 0 && $hasLifecycleCallbacks) {
                $this->errors[] = vsprintf('"HasLifecycleCallbacks" et inutile : aucun callback dans cette entité %s', [$this->classMetadata->name]);
            }
            // Manager
            if(!$this->classMetadata->isMappedSuperclass && !($this->manager instanceof AppEntityManagerInterface)) {
                $this->errors[] = vsprintf('"L\'entité %s ne possède pas de service assigné (utiliser un attribut de classe %s pour cela svp)', [$this->classMetadata->name, ClassCustomService::class]);
            }
            $this->computed = true;
        }
        return $this;
    }

    public function getClassAttributes(
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        return Classes::getClassAttributes($this->classMetadata->reflClass, $attributeClass, $searchParents);
    }

    public function getPropertyAttributes(
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        return Classes::getPropertyAttributes($this->classMetadata->reflClass, $attributeClass, $searchParents);
    }

    public function getMethodAttributes(
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        return Classes::getMethodAttributes($this->classMetadata->reflClass, $attributeClass, $searchParents);
    }

    public function getConstantAttributes(
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        return Classes::getConstantAttributes($this->classMetadata->reflClass, $attributeClass, $searchParents);
    }

    public function hasErrors(): bool
    {
        return !empty($this->computeReport()->errors);
    }

    public function getErrors(): array
    {
        return $this->computeReport()->errors;
    }

}