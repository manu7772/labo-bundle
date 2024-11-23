<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\AppEntityInfo;
use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Component\HydratedReferences;
use Aequation\LaboBundle\Component\Opresult;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\OwnerInterface;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Repository\Interface\CommonReposInterface;
use Aequation\LaboBundle\Service\AppService;
use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Service\Tools\HttpRequest;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Contracts\Cache\ItemInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;

use Closure;
use DateTime;
use DateTimeImmutable;
use Exception;
use Throwable;

#[AsAlias(AppEntityManagerInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class AppEntityManager extends BaseService implements AppEntityManagerInterface
{

    public const ENTITY = null;
    // public const APP_NAMESPACES_SEARCH = '/^(App|Aequation\\\\LaboBundle)\\\\Entity/';
    public const CACHE_ENTITY_REPORTS_NAME = 'app_entity_reports';
    public const CACHE_ENTITY_REPORTS_LIFE = null;
    // Validation groups
    public const VALID_GROUP_DEFAULT = 'default';
    public const VALID_GROUP_NEW = 'new';
    public const VALID_GROUP_EDIT = 'edit';
    public const VALID_GROUP_DRAFT = 'draft';
    public const VALID_GROUP_SENDMAIL = 'sendmail';
    public const All_GROUPS = ['default', 'new', 'edit', 'draft', 'sendmail'];
    public const VALIDATION_GROUPS = [
        'default'   => [],              // standard validation group
        'new'       => ['default'],     // for created entity
        'edit'      => ['default'],     // for updated entity
        'draft'     => [],              // partially valid, but can be persisted without doctrine error
        'sendmail'  => ['default'],     // is sendable
    ];

    protected readonly HydratedReferences $hydrateds;

    public function __construct(
        protected EntityManagerInterface $em,
        protected AppServiceInterface $appService,
        protected AccessDecisionManagerInterface $accessDecisionManager,
        protected ValidatorInterface $validator,
    ) {
        $this->hydrateds = new HydratedReferences();
    }

    public function getAppService(): AppServiceInterface
    {
        return $this->appService;
    }

    protected function needOverrideException(
        string $method,
        int $line,
    ): void
    {
        throw new Exception(vsprintf("%s class %s is invalid! This method %s() [line %d] needs to be overriden in sub classes.", [static::class, json_encode(static::ENTITY), $method ?? __METHOD__, $line ?? __LINE__]));
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }


    /****************************************************************************************************/
    /** INFO                                                                                          */
    /****************************************************************************************************/

    public function isDev(): bool
    {
        return $this->appService->isDev();
    }

    public function isProd(): bool
    {
        return $this->appService->isProd();
    }

    public function isTest(): bool
    {
        return $this->appService->isTest();
    }

    public function getEnvironment(): string
    {
        return $this->appService->getEnvironment();
    }

    // public function getEntityNamespaces(): array
    // {
    //     return $this->em->getConfiguration()->getEntityNamespaces();
    // }

    public static function isAppEntity(
        string|object $classname
    ): bool
    {
        return is_a($classname, AppEntityInterface::class, true);
    }

    public function getEntityNames(
        bool $asShortnames = false,
        bool $allnamespaces = false,
        bool $onlyInstantiables = false,
    ): array
    {
        $names = [];
        // or $this->em->getConfiguration()->getEntityNamespaces() as $classname
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $cmd) {
            /** @var ClassMetadata $cmd */
            if(!$onlyInstantiables || $cmd->reflClass->isInstantiable()) {
                if($allnamespaces || static::isAppEntity($cmd->name)) {
                    $names[$cmd->name] = $asShortnames
                        ? $cmd->reflClass->getShortname()
                        : $cmd->name;
                }
            }
        }
        return $names;
    }

    public function getEntityShortname(
        string|AppEntityInterface $objectOrClass = null
    ): string
    {
        $classname ??= static::ENTITY;
        $classname = $objectOrClass instanceof AppEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        return $classname
            ? $this->em->getClassMetadata($classname)->reflClass->getShortname()
            : null;
    }

    public function getClassnameByShortname(
        string $shortname
    ): string|false
    {
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $cmd) {
            /** @var ClassMetadata $cmd */
            if($shortname === $cmd->reflClass->getShortname()) return $cmd->name;
        }
        return false;
        // $names = $this->getEntityNames(true);
        // return array_search($shortname, $names);
    }

    public function entityExists(
        string|object $classname, // or shortname
        bool $allnamespaces = false,
        bool $onlyInstantiables = false,
    ): bool
    {
        if(is_object($classname)) $classname = get_class($classname);
        $list = $this->getEntityNames(true, $allnamespaces, $onlyInstantiables);
        return in_array($classname, $list) || array_key_exists($classname, $list);
    }

    public function getEntityNamesChoices(
        bool $asHtml = false,
        bool $icon = true, // only if $asHtml is true
        bool $allnamespaces = false,
        bool $onlyInstantiables = false
    ): array
    {
        return array_flip(array_map(function($name) use ($asHtml, $icon) {
            if($icon) $icon = '<i class="fa fa-'.$name::FA_ICON.' fa-fw text-info"></i>&nbsp;&nbsp;';
            return $asHtml
                ? static::getEntityNameAsHtml($name, $icon)
                : $name
                ;
        }, $this->getEntityNames(false, $allnamespaces, $onlyInstantiables)));
    }

    public static function getEntityNameAsHtml(
        string|AppEntityInterface $classOrEntity,
        string|bool $icon = true,
        bool $classname = true
    ): string
    {
        if(is_string($classOrEntity) && !class_exists($classOrEntity)) throw new Exception(vsprintf('Error %s line %d: entity of class %s does not exist!', [__METHOD__, __LINE__, json_encode($classOrEntity)]));
        if($classOrEntity instanceof AppEntityInterface) $classOrEntity = $classOrEntity->getClassname();
        $icon = $icon ? '<i class="fa fa-'.$classOrEntity::FA_ICON.' fa-fw text-info"></i>&nbsp;&nbsp;' : '';
        return $icon.'<strong>'.Classes::getShortname($classOrEntity, true).'</strong>'.($classname ? '&nbsp;<small><i class="text-muted">'.$classOrEntity.'</i></small>' : '');
    }

    /**
     * Get all relations of Entity as list of classnames => [(object) XxxMapping, string type]
     * 
     * @param string|AppEntityInterface $objectOrClass
     * @param string|array|null|null $relationTypes
     * @param boolean $excludeSelf
     * @return array
     */
    public function getRelateds(
        string|AppEntityInterface $objectOrClass,
        string|array|null $relationTypes = null,
        bool $excludeSelf = false
    ): array
    {
        $classname = $objectOrClass instanceof AppEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        if(empty($relationTypes)) $relationTypes = null;
        if(is_string($relationTypes)) $relationTypes = [$relationTypes];
        $classnames = [];
        foreach ($this->getEntityNames(false, false, true) as $class) {
            if(!($excludeSelf && is_a($class, $classname, true))) {
                foreach ($this->getClassMetadata($class)->associationMappings as $associationMapping) {
                    $shortname = Classes::getShortname($associationMapping, true);
                    preg_match('/^((Many|One)To(Many|One))/', $shortname, $types);
                    $type = reset($types);
                    if(!preg_match('/^((Many|One)To(Many|One))$/', $type)) throw new Exception(vsprintf('Error %s line %d: missing %s to class %s, got %s!', [__METHOD__, __LINE__, '((Many|One)To(Many|One))', get_class($associationMapping), $type]));
                    if(is_a($associationMapping->targetEntity, $classname, true) && (empty($relationTypes) || in_array($type, $relationTypes))) {
                        $classnames[$class] = [
                            'mapping_object' => $associationMapping,
                            'mapping_type' => $type,
                        ];
                    }
                }
            }
        }
        return $classnames;
    }

    public function getEntityMetadataReport(
        string $classname = null,
    ): ClassmetadataReport
    {
        $meta_infos = $this->getEntityMetadataReports();
        return $meta_infos[$classname ?? static::ENTITY];
    }

    public function getEntityMetadataReports(): array
    {
        if($this->isDev()) return $this->computeEntityMetadataReports();
        // 
        $reports = $this->appService->getCache()->get(
            key: static::CACHE_ENTITY_REPORTS_NAME,
            callback: function(ItemInterface $item) {
                if(!empty(static::CACHE_ENTITY_REPORTS_LIFE)) {
                    $item->expiresAfter(static::CACHE_ENTITY_REPORTS_LIFE);
                }
                return $this->computeEntityMetadataReports();
            },
            commentaire: 'All entities reports (meta infos)',
        );
        // dd($reports);
        return $reports;
    }

    // public function dumpReports(): void
    // {
    //     foreach ($this->computeEntityMetadataReports() as $test) {
    //         dump(json_encode($test));
    //     }
    // }

    private function computeEntityMetadataReports(): array
    {
        $meta_infos = [];
        foreach ($this->getEntityNames(false, false) as $classname) {
            $meta_infos[$classname] = new ClassmetadataReport($this, $classname);
            if($this->isDev() && !$meta_infos[$classname]->isValid()) {
                throw new Exception(vsprintf('Error %s line %d: %s of entity %s is invalid!', [__METHOD__, __LINE__, Classes::getShortname(ClassmetadataReport::class), $classname]));
            }
        }
        return $meta_infos;
    }

    public function getEntityMetadataReportsFiltered(
        Closure $filter
    ): array
    {
        return $filter($this->getEntityMetadataReports());
    }


    public static function getUniqueFields(
        string $classname,
        bool|null $flatlisted = false,
    ): array
    {
        $uniqueFields = [
            'hierar' => [],
            'flatlist' => [],
        ];
        foreach (Classes::getClassAttributes($classname, UniqueEntity::class, true) as $attr) {
            /** @var UniqueEntity $attr */
            $ufields = (array)$attr->fields;
            if(isset($ufields)) {
                $uniqueFields['hierar'][] = $ufields;
                $uniqueFields['flatlist'] = array_unique(array_merge($uniqueFields['flatlist'], $ufields));
            }
        }
        if(is_null($flatlisted)) return $uniqueFields;
        return $flatlisted
            ? $uniqueFields['flatlist']
            : $uniqueFields['hierar'];
    }


    /****************************************************************************************************/
    /** NORMALIZER / SERIALIZER                                                                         */
    /****************************************************************************************************/

    // public function getNormalized(
    //     mixed $data
    // ): array
    // {
    //     return $this->appService->getNormalized($data);
    // }


    /****************************************************************************************************/
    /** REPOSITORY / FIND                                                                               */
    /****************************************************************************************************/

    public function getRepository(
        string $classname = null,
        string $field = null // if field, find repository where is declared this $field
    ): CommonReposInterface
    {
        $origin_classname = $classname;
        $classname ??= static::ENTITY;
        if(empty($classname) && $this->isDev()) $this->needOverrideException(__METHOD__, __LINE__);
        // Check if not MAPPEDSUPERCLASS / not instantiable
        $cmd = $this->getClassMetadata($classname);
        $classname = $cmd->name;
        if($field) {
            // Find classname where field is declared
            if(array_key_exists($field, $cmd->fieldMappings)) {
                $test_classname = $cmd->fieldMappings[$field]->declared ?? $classname;
            } else if(array_key_exists($field, $cmd->associationMappings)) {
                $test_classname = $cmd->associationMappings[$field]->declared ?? $classname;
            } else {
                // Not found, tant pis...
            }
            if(isset($test_classname)) {
                $test_cmd = $this->getClassMetadata($test_classname);
                if(!$test_cmd->isMappedSuperclass) $classname = $test_classname;
            }
        }
        /** @var CommonReposInterface */
        $repo = $this->em->getRepository($classname);
        // if(!empty($field)) dump($classname, $field, get_class($repo));
        if(!($repo instanceof CommonReposInterface)) dd($this->__toString(), $origin_classname, $classname, $cmd, $cmd->name, $repo);
        return $repo;
    }

    /**
     * Get entity by EUID
     * @param string $euid
     * @return AppEntityInterface|null
     */
    public function findEntityByEuid(string $euid): ?AppEntityInterface
    {
        $class = Encoders::getClassOfEuid($euid);
        /** @var ServiceEntityRepositoryInterface */
        $repo = $this->getRepository($class);
        return $repo->findOneByEuid($euid);
    }

    public function getEntitiesCount(
        array $criteria = [],
    ): int
    {
        /** @var ServiceEntityRepositoryInterface */
        $repository = $this->getRepository();
        return $repository->count(criteria: $criteria);
    }

    public function findByUname(
        string $uname,
        bool $exceptionIfNotFound = true
    ): ?AppEntityInterface
    {
        $entity = $this->hydrateds->get($uname);
        if(empty($entity)) {
            // Try in database...
            $classes = $this->getEntityNames(false, false, true);
            foreach ($classes as $class) {
                if(is_a($class, UnamedInterface::class, true)) {
                    /** @var string $class */
                    /** @var ServiceEntityRepositoryInterface */
                    $repo = $this->getRepository($class);
                    $entity = $repo->findEntityByEuidOrUname($uname);
                    if(!empty($entity)) return $entity;
                }
            }
        }
        if($exceptionIfNotFound && !($entity instanceof AppEntityInterface)) {
            $refs = PHP_EOL;
            foreach ($this->hydrateds->getAllReferences() as $key) {
                $refs .= '- '.$key.PHP_EOL;
            }
            throw new Exception(vsprintf('Error %s line %d: could not find entity with uname "%s"'.PHP_EOL.'-> Searched in database and in: %s)!', [__METHOD__, __LINE__, $uname, $refs]));
        }
        return $entity instanceof AppEntityInterface ? $entity : null;
    }


    /****************************************************************************************************/
    /** ENTITY                                                                                          */
    /****************************************************************************************************/

    /**
     * Get ClassMetadata for Entity
     * @see https://phpdox.net/demo/Symfony2/classes/Doctrine_ORM_Mapping_ClassMetadata.xhtml
     * 
     * @param string|AppEntityInterface|null $objectOrClass
     * @return ClassMetadata|null
     */
    public function getClassMetadata(
        string|AppEntityInterface $objectOrClass = null,
    ): ?ClassMetadata
    {
        $classname = $objectOrClass instanceof AppEntityInterface ? $objectOrClass->getClassname() : $objectOrClass;
        return $classname
            ? $this->em->getClassMetadata($classname)
            : null;
    }

    final public static function getEntityServiceID(string|AppEntityInterface $objectOrClass): ?string
    {
        if(is_object($objectOrClass)) $objectOrClass = $objectOrClass->getClassname();
        $serviceName = AppService::getClassServiceName($objectOrClass);
        if(empty($serviceName)) {
            $attrs = Classes::getClassAttributes(static::class, AsAlias::class, true);
            if(count($attrs)) {
                /** @var AsAlias */
                $attr = reset($attrs);
                return $attr->id;
            }
        }
        return $serviceName;
    }

    public function getEntityService(
        string|AppEntityInterface $objectOrClass
    ): ?AppEntityManagerInterface
    {
        $id = $this->getEntityServiceID($objectOrClass);
        $service = $id ? $this->appService->get($id) : null;
        return $service instanceof AppEntityManagerInterface
            ? $service
            : null;
    }

    /**
     * Get all entity classnames of interfaces
     * @param string|array $interfaces
     * @param boolean $allnamespaces = false
     * @return array
     */
    public function getEntityClassesOfInterface(
        string|array $interfaces,
        bool $allnamespaces = false,
        bool $onlyInstantiables = false
    ): array
    {
        return array_filter(
            Classes::filterByInterface($interfaces, $this->getEntityNames(false, $allnamespaces)),
            fn ($class) => $this->entityExists($class, $allnamespaces, $onlyInstantiables)
        );
    }

    public function getScheduledForInsert(
        string|array|callable $filter = null
    ): array
    {
        /** @var UnitOfWork */
        $uow = $this->em->getUnitOfWork();
        $entities = $uow->getScheduledEntityInsertions();
        if(empty($filter)) return $entities;
        if(is_callable($filter)) {
            return array_filter($entities, $filter);
        }
        $filter = (array)$filter;
        return array_filter($entities, function($entity) use ($filter) {
            foreach ($filter as $class) {
                return is_a($entity, $class);
            }
        });
    }

    public function getScheduledForUpdate(
        string|array|callable $filter = null
    ): array
    {
        /** @var UnitOfWork */
        $uow = $this->em->getUnitOfWork();
        $entities = $uow->getScheduledEntityUpdates();
        if(empty($filter)) return $entities;
        if(is_callable($filter)) {
            return array_filter($entities, $filter);
        }
        $filter = (array)$filter;
        return array_filter($entities, function($entity) use ($filter) {
            foreach ($filter as $class) {
                return is_a($entity, $class);
            }
        });
    }

    public function getScheduledForDelete(
        string|array|callable $filter = null
    ): array
    {
        /** @var UnitOfWork */
        $uow = $this->em->getUnitOfWork();
        $entities = $uow->getScheduledEntityDeletions();
        if(empty($filter)) return $entities;
        if(is_callable($filter)) {
            return array_filter($entities, $filter);
        }
        $filter = (array)$filter;
        return array_filter($entities, function($entity) use ($filter) {
            foreach ($filter as $class) {
                return is_a($entity, $class);
            }
        });
    }

    public function isManaged(
        AppEntityInterface $entity
    ): bool
    {
        return $this->em->contains($entity);
    }

    /**
     * Generic creation of entity
     * @param ?string $classname
     * @param ?callable $postCreate
     * @return AppEntityInterface|false
     */
    public function getNew(
        string $classname = null,
        callable $postCreate = null,
        string $uname = null
    ): AppEntityInterface|false
    {
        $classname ??= static::ENTITY;
        if(!class_exists($classname) || !$this->entityExists($classname, false, true)) {
            throw new Exception(vsprintf("Error %s line %d: %s entity does not exist or is not instantiable", [__METHOD__, __LINE__, $classname]));
        }
        /** @var AppEntityInterface $new */
        $new = new $classname();
        if(!($new instanceof AppEntityInterface)) return false;
        $this->setManagerToEntity($new);
        $this->dispatchEvent($new, AppEvent::onCreate);
        if(!empty($uname) && $new instanceof UnamedInterface) {
            // set Uname
            $new->updateUname($uname);
        }
        return $this->initEntity($new, $postCreate);
    }

    public function getModel(
        string $classname = null,
        callable $postCreate = null,
        string|array|null $event = null
    ): AppEntityInterface|false
    {
        $model = $this->getNew($classname, $postCreate);
        if($model) {
            $model->_setModel(); // IMPORTANT!!!
            if(!empty($event)) {
                $this->dispatchEvent($model, $event);
            }
            if(is_callable($postCreate)) {
                $postCreate(entity: $model);
            }    
        }
        return $model;
    }

    public function initEntity(
        AppEntityInterface $entity,
        ?callable $postCreate = null,
        string|array|null $event = null
    ): AppEntityInterface
    {
        if($this->isDev() && !$entity->_appManaged->isNew()) {
            throw new Exception(vsprintf('Error %s line %d: can not initialize a managed entity %s "%s"!', [__METHOD__, __LINE__, $entity::class, $entity]));
        }
        if(!$entity->__isAppManaged() || $entity->_isClone()) {
            $this->setManagerToEntity($entity, $event);
        } else if(!empty($event)) {
            $this->dispatchEvent($entity, $event);
        }
        if(is_callable($postCreate)) {
            $postCreate(entity: $entity);
        }
        return $entity;
    }

    public final function setManagerToEntity(
        AppEntityInterface $entity,
        string|array|null $event = null
    ): AppEntityInterface
    {
        if(!$entity->__isAppManaged() || $entity->_isClone()) {
            $manager = $this->getEntityService($entity);
            $appEntityInfo = new AppEntityInfo($entity, $manager);
            if(!empty($event)) {
                $this->dispatchEvent($entity, $event);
            }
            if(!$appEntityInfo->isValid()) throw new Exception(vsprintf('Error %s line %d: %s for %s is invalid!', [__METHOD__, __LINE__, $appEntityInfo::class, static::class]));
        } else if($this->isDev()) {
            throw new Exception(vsprintf('Error %s line %d: entity %s "%s" has it\'s manager yet!', [__METHOD__, __LINE__, $entity::class, $entity]));
        }
        return $entity;
    }

    #[AppEvent(groups: AppEvent::onCreate)]
    public function onCreate(
        AppEntityInterface $entity,
        mixed $data = null,
        ?string $group = null,
    ): static
    {
        $this->getEntityManager()->getEventManager()->dispatchEvent(AppEvent::onCreate, new LifecycleEventArgs($entity, $this->getEntityManager()));
        return $this;
    }

    /**
     * Apply events on $entity
     * @param AppEntityInterface $entity
     * @param string|array $typeEvent
     * @return static
     */
    public final function dispatchEvent(
        AppEntityInterface $entity,
        string|array $typeEvent,
        array $data = [],
    ): static
    {
        foreach ((array)$typeEvent as $event) {
            if(AppEvent::hasEvent($event)) {
                return $this->executeAppEvent($entity, $event, $data);
            } else {
                $this->getEntityManager()->getEventManager()->dispatchEvent($event, new LifecycleEventArgs($entity, $this->getEntityManager()));
            }
        }
		return $this;
	}

    protected final function executeAppEvent(
        AppEntityInterface $entity,
        string $group = null,
        array $data = [],
    ): static
    {
        $event = AppEvent::class;
        // $this->setManagerToEntity($entity);
        // In service
        $applyed = false;
        foreach (Classes::getMethodAttributes($entity->_service, $event, true) as $reflAttrs) {
            foreach ($reflAttrs as $reflAttr) {
                /** @var AppEvent $reflAttr */
                $method = $reflAttr->method->name;
                if(!$this->isProd() && $method === __FUNCTION__) {
                    throw new Exception(vsprintf('Error %s line %d: can not define "%s" method in %s attribute without a risk of ininite loop!', [__METHOD__, __LINE__, __FUNCTION__, $event]));
                }
                if($reflAttr->isApplicable($entity, $group)) {
                    // dump(vsprintf('Apply event %s method %s (group: %s) to SERVICE %s for %s "%s" on %s line %d', [$event, $method, json_encode($group), $entity->_service->__toString(), $entity->getClassname(), $entity->__toString() ?? "null", __METHOD__, __LINE__]));
                    $entity->_service->$method($entity, $data, $group);
                    $applyed = true;
                }
            }
        }
        // In entity
        foreach (Classes::getMethodAttributes($entity, $event, true) as $reflAttrs) {
            foreach ($reflAttrs as $reflAttr) {
                /** @var AppEvent $reflAttr */
                $method = $reflAttr->method->name;
                if($reflAttr->isApplicable($entity, $group)) {
                    // dump(vsprintf('Apply event %s method %s (group: %s) to ENTITY %s "%s" on %s line %d', [$event, $method, json_encode($group), $entity->getClassname(), $entity->__toString() ?? "null", __METHOD__, __LINE__]));
                    $entity->$method($entity->_service, $data, $group);
                    $applyed = true;
                }
            }
        }
        if($applyed) $entity->_appManaged->setAppEventApplyed($group);
        return $this;
    }

    public final function clearAppEvents(
        AppEntityInterface $entity
    ): static
    {
        $entity->_appManaged->clearAppEvents();
        return $this;
    }

    // /**
    //  * Test event
    //  * @param AppEntityInterface $entity
    //  * @param mixed $data
    //  */
    // #[AppEvent(groups: [AppEvent::PRE_SET_DATA])]
    // public function testEvent(
    //     AppEntityInterface $entity,
    //     mixed $data = [],
    //     $group = null,
    // ): void
    // {
    //     if($this->isDev()) dump(vsprintf('Applying event %s of (%s) %s (line %d) on "%s" with data %s...', [$group, static::class, __METHOD__, __LINE__, $entity, json_encode($data)]));
    // }

    /**
     * Set owner (current User) to OwnerInterface entity
     * @param OwnerInterface $entity
     * @return static
     */
    public function defineEntityOwner(
        OwnerInterface $entity,
        bool $replace = false,
    ): static
    {
        if($replace || empty($entity->getOwner())) {
            $user = null;
            if(HttpRequest::isCli()) {
                $user = $this->getMainAdmin();
                if(empty($user)) $user = $this->getMainSAdmin();
            } else {
                $user = $this->getUser();
            }
            if($user instanceof LaboUserInterface) $entity->setOwner($user);
        }
        return $this;
    }

    public function checkPersistable(
        AppEntityInterface $entity
    ): bool
    {
        $check = !$entity->_isClone()
            && !$entity->_isModel()
            ;
        if(!$check && $this->isDev()) {
            $lines = [
                vsprintf('Erreur %s line %d: %s "%s" has errors and can not be persisted.', [__METHOD__, __LINE__, $entity->getClassname(), $entity]),
            ];
            if($entity->_isClone()) {
                $lines[] = vsprintf('Erreur %s line %d: %s "%s" should not be clone.', [__METHOD__, __LINE__, $entity->getClassname(), $entity]);
            }
            if($entity->_isModel()) {
                $lines[] = vsprintf('Erreur %s line %d: %s "%s" should not be model.', [__METHOD__, __LINE__, $entity->getClassname(), $entity]);
            }
            if(count($lines) > 0) {
                throw new Exception(implode(PHP_EOL, $lines));
            }
        }
        return $check;
    }

    protected function persist(
        AppEntityInterface $entity,
        bool|Opresult $opresultException = true
    ): bool
    {
        $this->checkPersistable($entity);
        $isNew = $entity->_appManaged->isNew();
        try {
            if($isNew) {
                // Before persit
                $this->executeAppEvent($entity, AppEvent::beforePrePersist, []);
                $this->em->persist($entity);
                // dump('Is NEW : '.json_encode($isNew).' > Persisted "'.$entity.'" (id: '.json_encode($entity->getId()).')... : '.json_encode($this->isManaged($entity)));
            } else {
                // Before update
                $this->executeAppEvent($entity, AppEvent::beforePreUpdate, []);
                // dump('Is NEW : '.json_encode($isNew).' > NOT Persisted "'.$entity.'" (id: '.json_encode($entity->getId()).')... : '.json_encode($this->isManaged($entity)));
            }
        } catch (Throwable $th) {
            if($opresultException instanceof Opresult) {
                $opresultException->addDanger(vsprintf('L\'enregistrement de %s %s a échoué%s', [$entity->getClassname(), $entity->__toString(), PHP_EOL.$th->getMessage()]));
            } else if($opresultException) {
                throw new Exception(vsprintf('Erreur %s line %d: L\'enregistrement a échoué ! Veuiller recommencer l\'opération, s.v.p.%s', [__METHOD__, __LINE__, PHP_EOL.$th->getMessage()]));
            }
            return false;
        }
        return true;
    }

    public function save(
        AppEntityInterface $entity,
        bool|Opresult $opresultException = true
    ): bool
    {
        // if(!$this->isManaged($entity)) {
            $this->persist($entity, $opresultException); // important!!! --> needed for updates too, to apply AppEvent::beforePreUpdate
        // }
        if($opresultException instanceof Opresult && !$opresultException->isSuccess()) {
            return false;
        }
        $this->em->flush();
        return true;
    }

    public function delete(
        AppEntityInterface $entity,
        bool|Opresult $opresultException = true
    ): bool
    {
        try {
            $this->executeAppEvent($entity, AppEvent::beforePreRemove, []);
            $this->em->remove($entity);
            $this->em->flush();
            if($opresultException instanceof Opresult) {
                $opresultException->addSuccess(vsprintf('L\'entité %s %s a été supprimée la base de données', [$entity->getClassname(), $entity->__toString()]));
            }
        } catch (Throwable $th) {
            if($opresultException instanceof Opresult) {
                $opresultException->addDanger(vsprintf('La suppression de %s %s a échoué', [$entity->getClassname(), $entity->__toString()]));
            } else if($opresultException) {
                throw new Exception(vsprintf("La suppression a échoué ! Veuiller recommencer l'opération, s.v.p.%s", [PHP_EOL.$th->__toString()]));
            }
            return false;
        }
        return true;
    }


    /*********************************************************************************************/
    /** USER / GRANTS                                                                            */
    /*********************************************************************************************/

    /**
     * Get current User
     * @return LaboUserInterface|null
     */
    public function getUser(): ?LaboUserInterface
    {
        return $this->appService->getUser();
    }

    public function getMainSAdmin(): ?LaboUserInterface
    {
        return $this->appService->getMainSAdmin();
    }

    public function getMainAdmin(): ?LaboUserInterface
    {
        return $this->appService->getMainAdmin();
    }

    public function isGranted(
        mixed $attributes,
        mixed $subject = null,
    ): bool
    {
        if($attributes instanceof LaboUserInterface) {
            $attributes = $attributes->getHigherRole();
        }
        return $this->appService->isGranted($attributes, $subject);
    }

    public function isUserGranted(
        LaboUserInterface $user,
        $attributes,
        $object = null,
        string $firewallName = 'none'
    ): bool
    {
        return $this->appService->isUserGranted($user, $attributes, $object, $firewallName);
    }

    public function checkEntityAccess(
        AppEntityInterface|string $entity,
        string $action,
        bool $throwException = false,
    ): bool
    {
        /** @var AppVoterInterface */
        // $voter = $entity->getShortnameDecorated(suffix: 'Voter');
        if(!$this->appService->isGranted($action, $entity)) {
            if($throwException) throw new Exception(message: vsprintf('Accès interdit pour action "%s" [firewall: "%s"] pour %s "%s".', [$action, $this->appService->getAppContext()->getFirewallName(), $entity->getShortname(), $entity->__toString()]), code: 403);
            return false;
        }
        return true;
    }

    /**
     * Is valid entity for an action (show, edit, remove, send, etc.)
     * @param AppEntityInterface $entity
     * @param string $action
     * @param LaboUserInterface|null $user
     * @return boolean
     */
    public function isValidForAction(
        AppEntityInterface $entity,
        string $action,
        ?LaboUserInterface $user = null,
    ): bool
    {
        // return false;
        // $user = $this->getUser();
        // $entityService = $this->getEntityService($entity);
        // return $entityService->isValidForAction($entity, $action, $user);
        return $this->isGranted($action, $entity);
    }


    /****************************************************************************************************/
    /** ENTITY HYDRATATION                                                                              */
    /****************************************************************************************************/

    public function hydrateEntity(
        AppEntityInterface|string $entity,
        array $data,
        string $uname = null,
    ): AppEntityInterface|false
    {
        if(is_string($entity) && $this->entityExists($entity)) {
            $ClassCustomService = $this->getEntityService($entity);
            $entity = $ClassCustomService->getNew(classname: $entity, uname: !empty($uname) ? $uname : null);
        }
        $cmd = $this->getClassMetadata($entity);
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        foreach ($data as $property => $value) {
            switch (true) {
                case $property === '_reverse':
                    foreach ($value as $uname => $fields) {
                        $target = $this->findByUname($uname, true);
                        if($target) {
                            foreach ((array)$fields as $field) {
                                $propertyAccessor->setValue($target, $field, $entity);
                            }
                        }
                    }
                    break;
                case array_key_exists($property, $cmd->fieldMappings):
                    // Column
                    switch($cmd->fieldMappings[$property]['type']) {
                        // case 'json':
                        //     $propertyAccessor->setValue($entity, $property, (array)$value);
                        //     break;
                        case 'boolean':
                            $propertyAccessor->setValue($entity, $property, (bool)$value);
                            break;
                        case 'datetime_immutable':
                            $propertyAccessor->setValue($entity, $property, new DateTimeImmutable($value));
                            break;
                        case 'datetime':
                            $propertyAccessor->setValue($entity, $property, new DateTime($value));
                            break;
                        default:
                            // Try by default...
                            try {
                                $propertyAccessor->setValue($entity, $property, $value);
                            } catch (Throwable $th) {
                                throw $th;
                                // Sinon, tant pis...
                            }
                            break;
                    }
                    break;
                case array_key_exists($property, $cmd->associationMappings):
                    // Relation
                    $type = Classes::getShortname($cmd->associationMappings[$property], true);
                    $isMany = preg_match('/^(One|Many)ToMany/', $type);
                    $target_class = $cmd->associationMappings[$property]->targetEntity;
                    $dbl = (array)$value;
                    if(!$isMany) $dbl = count($dbl) > 0 ? array_slice($dbl,0,1,true) : [];
                    if(count($dbl) > 0) {
                        foreach ($dbl as $key => $val) {
                            if(is_array($val)) {
                                // Create entity
                                $dbl[$key] = $this->hydrateEntity($target_class, $val, is_string($key) && !empty($key) ? $key : null);
                            } else {
                                // Find entity by Uname
                                $dbl[$key] = $this->findByUname($val, false);
                            }
                        }
                        $value = array_filter($dbl, fn($item) => $item instanceof $target_class);
                        if(!$isMany) $value = reset($dbl);
                        if(!empty($value)) $propertyAccessor->setValue($entity, $property, $value);
                    }
                    break;
                default:
                    $value = Classes::transtype($entity, $property, $value);
                    // Property as data is setter
                    if(method_exists($property, $entity)) {
                        $entity->$property($value);
                    } else {
                        // Other properties
                        $propertyAccessor->setValue($entity, $property, $value);
                    }
                    break;
            }
        }
        // Add images
        // $this->addPhotos($entity, static::IS_TEST ? 1 : $this->faker->numberBetween(2, 4));
        // Validity
        $this->checkEntityValidity(entity: $entity, throw: true);
        return $entity;
    }

    /**
     * Create entities from YAML file(s)
     * @param string|null $path
     * @param boolean $persist
     * @param boolean $doflush
     * @param array $classes
     * @param SymfonyStyle|null $io
     * @return Opresult
     */
    public function loadEntities(
        string $path = null,
        bool $replace = false,
        bool $persist = true,
        array|string $classes = [],
        ?SymfonyStyle $io = null,
    ): Opresult
    {
        $tool_files = $this->appService->get('Tool:Files');
        $classes = empty($classes) ? [] : (array)$classes;
        $entities = $this->getEntityNames(false, false, true);
        if(empty($classes)) $classes = $entities;
        $classes = array_intersect($classes, $entities);
        $result = new Opresult();
        $path ??= $this->appService->getParameter('basics_dir').'/data';
        $ymlFiles = $tool_files->listFiles(path: $path, filter: ['*.yaml','*.yml']);
        $alldata = [];
        $total = 0;
        foreach ($ymlFiles as $file) {
            $data = $tool_files->readYamlFile($file->getRealpath());
            if(isset($data['data']) && in_array($data['data']['entity'] ?? null, $entities) && count($data['data']['items'] ?? []) > 0) {
                if(empty($classes) || in_array($data['data']['entity'], $classes)) {
                    $id = intval($data['data']['order']);
                    if($id <= 0) throw new Exception(vsprintf('Index %d (with intval of %s) can not exists for %s: must be > 0!', [$id, $data['data']['order'], $data['data']['entity']]));
                    if(isset($alldata[$id])) throw new Exception(vsprintf('Index %d already exists for %s!', [$id, $data['data']['entity']]));
                    $alldata[$id] = $data['data'];
                }
            }
        }
        // $result->addData('all_data', $alldata);
        if(count($alldata)) {
            ksort($alldata);
            foreach ($alldata as $data) {
                if($io) $io->info(vsprintf('Génération de %s : %d éléments', [$data['entity'], count($data['items'])]));
                $cpt = 0;
                foreach ($data['items'] as $key => $item) {
                    $total++;
                    $search = $this->getRepository($data['entity'])->tryFindExistingEntity(is_string($key) ? $key : $item);
                    $classOrEntity = $search instanceof $data['entity'] ? $search : $data['entity'];
                    if(is_string($classOrEntity) || $replace) {
                        if(is_a($classOrEntity, AppEntityInterface::class, true)) {
                            if($new_entity = $this->hydrateEntity($classOrEntity, $item, is_string($key) ? $key : null)) {
                                // Add reference
                                $this->checkEntityValidity(entity: $new_entity, throw: true);
                                // if($errors->count() > 0) {
                                //     // Enity is invalid
                                //     $errortxt = PHP_EOL.'---> '.implode(PHP_EOL.'---> ', (array)$errors->getIterator());
                                //     $io->error($errortxt);
                                //     $result->addDanger(vsprintf('- %s "%s" invalide : %s', [$new_entity->getShortname(), $new_entity->__toString(), $errortxt]));
                                // } else {
                                    $this->hydrateds->add($new_entity);
                                    if($io) {
                                        if($new_entity->_appManaged->isPersisted()) {
                                            $io->writeln(vsprintf('%d - %s "%s" existant : mise à jour', [++$cpt, $new_entity->getShortname(), $new_entity->__toString()]));
                                        } else {
                                            $io->writeln(vsprintf('%d - %s "%s" créé', [++$cpt, $new_entity->getShortname(), $new_entity->__toString()]));
                                        }
                                    }
                                    $this->persist($new_entity, $result);
                                // }
                            } else {
                                $result->addDanger(vsprintf('Génération impossible : cette entité "%s" n\'existe pas', [$data['entity']]));
                            }
                        } else {
                            $message = vsprintf('Génération impossible : cette entité "%s" n\'existe pas', [$data['entity']]);
                            if($io) $io->warning($message);
                            $result->addWarning($message);
                        }
                    } else {
                        $message = vsprintf('- %s "%s" existant : pas de mise à jour', [$classOrEntity->getShortname(), $classOrEntity->__toString()]);
                        if($io) $io->writeln($message);
                        $result->addUndone($message);
                    }
                }
            }
            if($persist) {
                foreach ($this->hydrateds->toArray() as $entity) {
                    // $this->save($entity, $result);
                    $test = $this->save($entity, true);
                    if($io) {
                        if($test) {
                            $io->info(vsprintf('- %s "%s" enregistrée', [$entity->getShortname(), $entity->__toString()]));
                        } else {
                            $io->error(vsprintf('- %s "%s" NON enregistrée', [$entity->getShortname(), $entity->__toString()]));
                        }
                    }
                }
                $this->hydrateds->clear();
            } else {
                $count = $this->hydrateds->count();
                if($count > 0) {
                    $result->addSuccess(vsprintf('Génération de %d enitités (non enregistrées)', [$count]), $count);
                } else {
                    $result->addUndone(vsprintf('Génération manquée de %d enitités sur %d (non enregistrées)', [$count, $total], $total));
                }
            }
        } else {
            $result->addUndone(vsprintf('Aucune donnée pour la génération. Désolé.', []));
        }
        $result->addData('total', $total);
        // dd($result->getContainer()); die('--- END ---');
        return $result;
    }


    /****************************************************************************************************/
    /** ENTITY VALIDATION                                                                               */
    /****************************************************************************************************/

    /**
     * Get default validation group name
     * @return string
     */
    public static function getDefaultValidationGroup(): string
    {
        return array_key_first(static::VALIDATION_GROUPS);
    }

    /**
     * Get all validation groups names
     * @return array
     */
    public static function getValidationGroups(): array
    {
        return array_keys(static::VALIDATION_GROUPS);
    }

    /**
     * Has validation group
     * @param string $group
     * @return boolean
     */
    public static function hasValidationGroup(
        string $group,
    ): bool
    {
        return array_key_exists($group, static::VALIDATION_GROUPS);
    }

    /**
     * Get all validation groups by group(s)
     * If empty, returns array with default validation group
     * @param null|array|string|null $groups
     * @param bool $makeExceptionIfGroupNotFound
     * @return array
     */
    public static function getValidationGroupsByGroups(
        null|array|string $groups = null,
        bool $makeExceptionIfGroupNotFound = true,
    ): array
    {
        if(empty($groups)) $groups = [static::getDefaultValidationGroup()];
        $validation_groups = [];
        foreach ((array)$groups as $group) {
            if(static::hasValidationGroup($group)) {
                $validation_groups = array_unique(array_merge($validation_groups, static::VALIDATION_GROUPS[$group]));
            } else if($makeExceptionIfGroupNotFound) {
                throw new Exception(vsprintf('Validation group "%s" does not exist!', [$group]));
            }
        }
        return $validation_groups;
    }

    public function checkEntityValidity(
        AppEntityInterface $entity,
        null|string|array $groups = null,
        bool $throw = true,
    ): ConstraintViolationList
    {
        $groups = static::getValidationGroupsByGroups($groups, true);
        /** @var ConstraintViolationList */
        $errors = $this->validator->validate(value: $entity, groups: $groups);
        if($errors->count() > 0) {
            if($throw) throw new Exception(vsprintf("!!!!! %s \"%s\" is invalid !!!!!".PHP_EOL."%s".PHP_EOL, [$entity::class, $entity->__toString(), PHP_EOL.'---> '.implode(PHP_EOL.'---> ', (array)$errors->getIterator())]));
        };
        return $errors;
    }

    public function isValidEntity(
        AppEntityInterface $entity,
        null|string|array $groups = null,
    ): bool
    {
        return $this->checkEntityValidity(entity: $entity, groups: $groups, throw: false)->count() <= 0;
    }


}