<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\SiteparamsRepositoryInterface;
use Aequation\LaboBundle\Entity\Siteparams;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends CommonRepos<Siteparams>
 *
 * @method Siteparams|null find($id, $lockMode = null, $lockVersion = null)
 * @method Siteparams|null findOneBy(array $criteria, array $orderBy = null)
 * @method Siteparams[]    findAll()
 * @method Siteparams[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[AsAlias(SiteparamsRepositoryInterface::class, public: true)]
class SiteparamsRepository extends CommonRepos implements SiteparamsRepositoryInterface
{

    const ENTITY_CLASS = Siteparams::class;
    const NAME = 'siteparams';


    public function findAllAsArray(): array
    {
        $qb = $this->createQueryBuilder(static::NAME);
        return $qb->getQuery()->getArrayResult();
    }

    public function findValids(): array
    {
        return $this->findAll();
    }

//    /**
//     * @return Siteparams[] Returns an array of Siteparams objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Siteparams
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}