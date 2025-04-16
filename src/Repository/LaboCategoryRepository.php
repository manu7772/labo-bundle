<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Model\Final\FinalCategoryInterface;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\LaboCategoryRepositoryInterface;
// Symfony
use Doctrine\ORM\QueryBuilder;

/**
 * @extends CommonRepos<FinalCategoryInterface>
 *
 * @method FinalCategoryInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method FinalCategoryInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method FinalCategoryInterface[]    findAll()
 * @method FinalCategoryInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
// #[AsAlias(LaboCategoryRepositoryInterface::class, public: true)]
abstract class LaboCategoryRepository extends CommonRepos implements LaboCategoryRepositoryInterface
{

    const ENTITY_CLASS = FinalCategoryInterface::class;
    const NAME = 'labocategory';

    public static function QB_CategoryChoices(
        QueryBuilder $qb,
        string|array $classnames,
        bool $addDefault = true,
    ): QueryBuilder
    {
        $classnames = (array)$classnames;
        if($addDefault) $classnames[] = FinalCategoryInterface::DEFAULT_TYPE;
        $alias = static::getAlias($qb);
        $qb->where($alias.'.type IN (:names)')
            ->setParameter('names', array_unique($classnames))
            ;
        return $qb;
    }

}