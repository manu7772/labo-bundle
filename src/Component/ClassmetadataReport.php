<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Model\Attribute\ClassCustomService;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Strings;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Twig\Markup;

use Exception;
use JsonSerializable;
use ReflectionClass;
use Serializable;

class ClassmetadataReport implements JsonSerializable, Serializable
{

    public readonly string $classname;
    protected ?AppEntityInterface $entity = null;
    public readonly ClassMetadata $classMetadata;
    public readonly ?AppEntityManagerInterface $manager;
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

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

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
        ];
        // $rc = new ReflectionClass(static::class);
        // foreach ($rc->getProperties() as $prop) {
        //     $name = $prop->name;
        //     $data[$name] = $this->$name;
        // }
        return $data;
    }




    public function isValid(): bool
    {
        return isset($this->classname)
            && isset($this->classMetadata)
            && isset($this->manager)
            && $this->appEntityManager->entityExists($this->classname)
            ;
    }

    public function setEntity(
        AppEntityInterface $entity
    ): static
    {
        if(!isset($this->classname)) {
            throw new Exception(vsprintf('Error %s line %d: classname is not defined!', [__METHOD__, __LINE__]));
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

    public function setClassname(
        string $classname
    ): bool
    {

        if($this->appEntityManager->entityExists($classname)) {
            $this->classname = $classname;
            $this->classMetadata = $this->appEntityManager->getClassMetadata($this->classname);
            $this->manager = $this->appEntityManager->getEntityService($this->classMetadata->name) ?? $this->appEntityManager;
        }
        return $this->isValid();
    }

    public function getData(): array|false
    {
        if($this->isValid()) {
            return [
                'classname' => $this->classname,
                'parent_classes' => $this->getParentClasses(),
            ];
        }
        return false;
    }

    public function __isset($name)
    {
        return property_exists($this->classMetadata, $name);
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

    public function getManangerID(): ?string
    {
        return $this->manager;
    }

    public function getShortname(): string
    {
        return $this->reflClass->getShortname();
    }

    public function getShortname_lower(): string
    {
        return strtolower($this->reflClass->getShortname());
    }

    public function getBreadcrumbName(
        string $link = ' \\ ',
        bool $asHtml = true,
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
        return Classes::getInheritedClasses($this->classMetadata->name, false, array_values($this->appEntityManager->getEntityNames(false)), $onlyInstantiables);
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
        bool $flatlisted = false,
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

    // public static function getHierarchizedReports(
    //     array $reports,
    //     ?array $hierarchs = null
    // ): array
    // {
    //     // static::sortReports($reports);
    //     $hierarchs ??= [];
    //     foreach ($reports as $key => $report) {
    //         if($report->isPhpRoot()) {
    //             $hierarchs[$report->reflClass->name] = [
    //                 'report' => $report,
    //                 'children' => [],
    //             ];
    //             unset($reports[$key]);
    //         }
    //     }
    //     return $hierarchs;
    // }

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
                        if(preg_match('/^Doctrine\\\\ORM\\\\Mapping/', $refattr->getName())) {
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

    public function getPropertysAttributes(
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        return Classes::getPropertysAttributes($this->classMetadata->reflClass, $attributeClass, $searchParents);
    }

    public function getMethodsAttributes(
        string $attributeClass,
        bool $searchParents = true,
    ): array
    {
        return Classes::getMethodsAttributes($this->classMetadata->reflClass, $attributeClass, $searchParents);
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