<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\LaboCategory;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\LaboCategoryRepositoryInterface;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends CommonRepos<Image>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
// #[AsAlias(LaboCategoryRepositoryInterface::class, public: true)]
abstract class LaboCategoryRepository extends CommonRepos implements LaboCategoryRepositoryInterface
{

    const ENTITY_CLASS = LaboCategory::class;
    const NAME = 'labocategory';

    public static function QB_CategoryChoices(
        QueryBuilder $qb,
        string|array $classnames,
        bool $addDefault = true,
    ): QueryBuilder
    {
        $classnames = (array)$classnames;
        if($addDefault) $classnames[] = LaboCategory::DEFAULT_TYPE;
        $alias = static::getAlias($qb);
        $qb->where($alias.'.type IN (:names)')
            ->setParameter('names', array_unique($classnames))
            ;
        return $qb;
    }

}