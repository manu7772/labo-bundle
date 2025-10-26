<?php
namespace Aequation\LaboBundle\Repository\Base;

use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
use Aequation\LaboBundle\Model\Interface\HasOrderedInterface;
use Aequation\LaboBundle\Model\Interface\LaboCategoryInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Repository\Interface\CommonReposInterface;
use Aequation\LaboBundle\Service\AppEntityManager;
use Aequation\LaboBundle\Service\AppService;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

use Exception;

abstract class CommonRepos extends ServiceEntityRepository implements CommonReposInterface
{

    const ENTITY_CLASS = '';
    const NAME = 'u';

    protected AppService $appService;

    public function __construct(
        ManagerRegistry $registry,
        AppService $appService,
    )
    {
        parent::__construct(registry: $registry, entityClass: static::ENTITY_CLASS);
        $this->appService = $appService;
    }


    /*************************************************************************************************/
    /** BASE TOOLS                                                                                   */
    /*************************************************************************************************/

    public function hasField(string $name): bool
    {
        $cmd = $this->getClassMetadata();
        return array_key_exists($name, $cmd->fieldMappings);
    }

    public function hasRelation(string $name): bool
    {
        $cmd = $this->getClassMetadata();
        return array_key_exists($name, $cmd->associationMappings);
    }

    protected static function getAlias(QueryBuilder $qb): ?string
    {
        $from = $qb->getDQLPart('from');
        /** @var From */
        $from = reset($from);
        $aliases = $qb->getRootAliases();
        if($from instanceof From) return $from->getAlias();
        return count($aliases) ? reset($aliases) : null;
    }

    protected static function getFrom(QueryBuilder $qb): ?string
    {
        $from = $qb->getDQLPart('from');
        /** @var From */
        $from = reset($from);
        return $from instanceof From ? $from->getFrom() : null;
    }

    public function isPublic(): bool
    {
        return $this->appService->isPublic();
    }

    public function isPrivate(): bool
    {
        return $this->appService->isPrivate();
    }


    /*************************************************************************************************/
    /** FORM TYPE UTILITIES                                                                          */
    /*************************************************************************************************/

    public function getChoicesForType(
        string $field,
        string $context = 'form_choice',
        ?array $search = [],
        // string $groupby = null,
    ): array
    {
        // $qb = $this->createQueryBuilder(static::NAME);
        // $this->__context_Qb(qb: $qb, context: $context);
        $qb = $this->getQB_findBy(search: $search, context: $context);
        $alias = static::getAlias($qb);
        // if($alias === 'u') dd($alias, $qb);
        $qb->select($alias.'.id');
        if(!in_array($field, ['id'])) {
            $qb->addSelect($alias.'.'.$field);
        }
        $results = $qb->getQuery()->getArrayResult();
        $choices = [];
        foreach ($results as $result) {
            $choices[$result[$field]] = $result['id'];
        }
        return $choices;
    }

    public function getCollectionChoices(
        string|HasOrderedInterface $classOrEntity,
        string $property,
        array $exclude_ids = [],
    ): array
    {
        $qb = $this->createQueryBuilder(static::NAME);
        if($this->appService->isDev()) {
            // Check
            $this->checkFromAndSearchClasses($qb, $classOrEntity::ITEMS_ACCEPT[$property], true);
        }
        static::getQB_orderedChoicesList($qb, $classOrEntity, $property, $exclude_ids);
        return $qb->getQuery()->getResult();
    }

    public static function getQB_orderedChoicesList(
        QueryBuilder $qb,
        string|HasOrderedInterface $classOrEntity,
        string $property,
        array $exclude_ids = [],
    ): QueryBuilder
    {
        $classes = $classOrEntity::ITEMS_ACCEPT[$property];
        // $qb ??= $this->createQueryBuilder(static::NAME);
        $alias = static::getAlias($qb);

        $whr = "($alias.classname IN (:classnames) OR $alias.shortname IN (:shortnames))";
        $parameters = new ArrayCollection([
            new Parameter('classnames', $classes),
            new Parameter('shortnames', $classes),
        ]);
        if(!empty($exclude_ids)) {
            $whr .= " AND $alias.id NOT IN (:ids)";
            $parameters->add(new Parameter('ids', $exclude_ids));
        }
        $qb->where($whr);
        $qb->setParameters($parameters);

        // $qb->andWhere($alias.'.classname IN (:classname)')
        //     ->setParameter('classname', $classes);
        // $qb->orWhere($alias.'.shortname IN (:shortname)')
        //     ->setParameter('shortname', $classes);
        // if(!empty($exclude_ids)) {
        //     $qb->andWhere($alias.'.id NOT IN (:ids)')
        //     ->setParameter('ids', $exclude_ids);
        // }
        static::__filter_Enabled($qb);
        return $qb;
    }

    public static function checkFromAndSearchClasses(
        QueryBuilder $qb,
        array|string $classes,
        bool $throwsException = false,
    ): bool
    {
        $from = static::getFrom($qb);
        foreach ((array)$classes as $class) {
            if(!is_a($class, $from, true)) {
                if($throwsException) {
                    throw new Exception(vsprintf('Error %s line %d: entities of class %s can not be found in %s class', [__METHOD__, __LINE__, $class, $from]));
                }
                return false;
            }
        }
        return true;
    }


    /*************************************************************************************************/
    /** TRY FIND BY CATEGORYS                                                                        */
    /*************************************************************************************************/

    public function findByCategorys(
        string|array $categories,
        ?array $search = null,
        string $context = 'auto',
    ): array
    {
        $qb = $this->getQB_findBy(search: $search, context: $context);
        if($this->hasRelation('categorys')) {
            // dump($categories, $search, $context);
            $categories = (array)$categories;
            $alias = static::getAlias($qb);
            if(count($categories)) {
                $qb->leftJoin($alias.'.categorys', 'categorys');
                // $qb->addSelect('COUNT(categorys) AS HIDDEN catsCount');
                // $qb->having($qb->expr()->gte('catsCount', 1));
                if(array_is_list($categories)) {
                    $qb->andWhere('categorys.name IN (:cats)')
                        ->setParameter('cats', array_map(fn($cat) => is_object($cat) ? $cat->getName() : $cat, $categories))
                        ->groupBy($alias.'.id, categorys.id')
                        ;
                } else {
                    foreach ($categories as $field => $cats) {
                        if(!is_array($cats)) $cats = [$cats];
                        if(count($cats)) {
                            $qb->andWhere('categorys.'.$field.' IN (:'.$alias.'_'.$field.'s)')
                                ->setParameter($alias.'_'.$field.'s', array_map(fn($cat) => is_object($cat) ? $cat->getName() : $cat, $cats))
                                ;
                        }
                    }
                    $qb->groupBy($alias.'.id, categorys.id');
                }
            }
        }
        $result = $qb->getQuery()->getResult();
        // dump($qb->getDQL(), $result);
        return $result;
    }


    /*************************************************************************************************/
    /** TRY FIND BY HIDRATATION DATA                                                                 */
    /*************************************************************************************************/

    public function tryFindExistingEntity(
        string|array $dataOrUname,
        ?array $uniqueFields = null,
    ): ?AppEntityInterface
    {
        if(is_array($dataOrUname)) {
            // Got array of data
            if(empty($uniqueFields)) $uniqueFields = AppEntityManager::getUniqueFields(static::ENTITY_CLASS, false);
            if(count($uniqueFields)) {
                if(!is_array(reset($uniqueFields))) $uniqueFields = [$uniqueFields];
                foreach ($uniqueFields as $fields) {
                    $search = [];
                    foreach ($fields as $field) {
                        if(isset($dataOrUname[$field])) $search[$field] = $dataOrUname[$field];
                    }
                    if(!empty($search)) {
                        $find = $this->findOneBy($search);
                        if($find) return $find;
                    }
                }
            }
        }
        return is_string($dataOrUname)
            ? $this->findEntityByEuidOrUname($dataOrUname) // Got Uname
            : null;
    }

    public function findEntityByEuidOrUname(
        string $uname
    ): ?AppEntityInterface
    {
        $qb = $this->createQueryBuilder(static::NAME)
            ->where(static::NAME.'.euid = :uname')
            ->setParameter('uname', $uname);
        if($this->hasRelation('uname')) {
            $qb->leftJoin(static::NAME.'.uname', 'uname')
                ->orWhere('uname.uname = :uname');
        }
        return $qb->getQuery()->getOneOrNullResult();
    }


    /*************************************************************************************************/
    /** SLUGGABLE ENTITIES                                                                           */
    /*************************************************************************************************/

    /**
     * Find all slugs of a class
     * @param integer|null $exclude_id
     * @return array
     */
    public function findAllSlugs(
        ?int $exclude_id = null
    ): array
    {
        $slugs = [];
        if(is_a($this->getEntityName(), SlugInterface::class, true)) {
            $qb = $this->createQueryBuilder(static::NAME)->select(static::NAME.'.id, '.static::NAME.'.slug');
            if(!empty($exclude_id)) {
                $qb->where(static::NAME.'.id != :exclude')->setParameter('exclude', $exclude_id);
            }
            $results = $qb->getQuery()->getArrayResult();
            foreach ($results as $result) {
                $slugs[$result['id']] = $result['slug'];
            }
        }
        return $slugs;
    }

    /**
     * Find all euids of a class
     * @param integer|null $exclude_euid
     * @return array
     */
    public function findAllEuids(
        ?string $exclude_euid = null
    ): array
    {
        $euids = [];
        if(is_a($this->getEntityName(), AppEntityInterface::class, true)) {
            $qb = $this->createQueryBuilder(static::NAME)->select(static::NAME.'.euid');
            if(!empty($exclude_euid)) {
                $qb->where(static::NAME.'.euid != :exclude')->setParameter('exclude', $exclude_euid);
            }
            $results = $qb->getQuery()->getArrayResult();
            foreach ($results as $result) {
                $euids[$result['euid']] = $result['euid'];
            }
        }
        return $euids;
    }

    /*************************************************************************************************/
    /** add to QueryBuilders                                                                         */
    /*************************************************************************************************/

    protected function __context_Qb(
        QueryBuilder $qb,
        string $context = 'auto',
    ): void
    {
        switch ($context) {
            case 'form_choice':
                static::__filter_Enabled($qb);
                $this->__filter_User($qb);
                break;
            default:
                // AUTO
                if($this->isPublic()) {
                    static::__filter_Enabled($qb);
                    $this->__filter_User($qb);
                }
                break;
        }
    }

    public static function __filter_Enabled(QueryBuilder $qb): void
    {   
        if(is_a(object_or_class: static::getFrom($qb), class: EnabledInterface::class, allow_string: true)) {
            $alias = static::getAlias($qb);
            $qb->andWhere($alias.'.enabled = :enabled')
                ->setParameter('enabled', true)
                ->andWhere($alias.'.softdeleted = :softd')
                ->setParameter('softd', false)
                ;
        }
    }

    protected function __filter_User(QueryBuilder $qb): void
    {
        if(is_a(object_or_class: $this->getEntityName(), class: LaboUserInterface::class, allow_string: true)) {
            $alias = static::getAlias($qb);
            $qb->andWhere($alias.'.expiresAt IS NULL OR '.$alias.'.expiresAt > :now')
                ->setParameter('now', $this->appService->getCurrentDatetime())
                ;
        }
    }

    protected function __filter_by(
        QueryBuilder $qb,
        array $search,
    ): void
    {
        $alias = static::getAlias($qb);
        foreach ($search as $field => $value) {
            $fields = explode('.', $field);
            $name = reset($fields);
            $neg = '';
            if(preg_match('/^!/', $name)) {
                $neg = is_array($value) ? 'NOT ' : '!';
                $name = preg_replace('/^!*/', '', $name);
            }
            switch (true) {
                case array_key_exists($name, $this->getClassMetadata()->fieldMappings):
                    # field
                    $comp = is_array($value) ? ' '.$neg.'IN (:'.$name.')' : ' '.$neg.'= :'.$name;
                    $qb->andWhere($alias.'.'.$name.$comp)
                        ->setParameter($name, $value)
                        ;
                    break;
                case array_key_exists($name, $this->getClassMetadata()->associationMappings):
                    # association
                    // dd($search, $alias.'.'.$name);
                    $v = reset($value);
                    if($v) {
                        $v = $v->getId();
                        // $comp = is_array($value) ? ' '.$neg.'IN (:'.$name.')' : ' '.$neg.'= :'.$name;
                        $qb->leftJoin($alias.'.'.$name, $name)
                            // ->andWhere($name.$comp)
                            ->andWhere($qb->expr()->in($name, [$v]))
                            // ->setParameter($name, $value)
                            ;
                    }
                    break;
                default:
                    throw new Exception(vsprintf('Field or association named "%s" (searching with "%s" with value "%s") not found!', [reset($fields), $field, (string)$value]));
                    break;
            }
        }
    }

    /*************************************************************************************************/
    /** get QueryBuilders                                                                            */
    /*************************************************************************************************/

    public function getQB_findBy(
        ?array $search,
        string $context = 'auto',
    ): QueryBuilder
    {
        $qb = $this->createQueryBuilder(static::NAME);
        $this->__context_Qb(qb: $qb, context: $context);
        if(!empty($search)) {
            $this->__filter_by(qb: $qb, search: $search);
            // dd($context, $search, static::getAlias($qb), $qb, $this->getClassMetadata());
        }
        return $qb;
    }

}