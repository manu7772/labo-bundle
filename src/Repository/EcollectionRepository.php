<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\Ecollection;
use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Aequation\LaboBundle\Model\Interface\HasOrderedInterface;
use Aequation\LaboBundle\Repository\ItemRepository;
use Aequation\LaboBundle\Repository\Interface\EcollectionRepositoryInterface;

use Doctrine\ORM\QueryBuilder;

/**
 * @extends ItemRepository<Ecollection>
 *
 * @method Ecollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ecollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ecollection[]    findAll()
 * @method Ecollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EcollectionRepository extends ItemRepository implements EcollectionRepositoryInterface
{

    const ENTITY_CLASS = Ecollection::class;
    const NAME = 'ecollection';


    public static function QB_collectionChoices(
        QueryBuilder $qb,
        string|EcollectionInterface $classname,
        string $field
    ): QueryBuilder
    {
        if(is_a($classname, EcollectionInterface::class, is_string($classname))) {
            $alias = static::getAlias($qb);
            $qb->where($alias.'.classname IN (:names)')
                ->setParameter('names', $classname::ITEMS_ACCEPT[$field])
                ;
        }
        return $qb;
    }

}
