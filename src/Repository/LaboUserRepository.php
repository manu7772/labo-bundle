<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\LaboUserRepositoryInterface;
use Aequation\LaboBundle\Service\Tools\Emails;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<LaboUser>
 *
 * @implements PasswordUpgraderInterface<LaboUser>
 *
 * @method LaboUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method LaboUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method LaboUser[]    findAll()
 * @method LaboUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
// #[AsAlias(LaboUserRepositoryInterface::class, public: true)]
class LaboUserRepository extends CommonRepos implements PasswordUpgraderInterface, LaboUserRepositoryInterface
{

    const ENTITY_CLASS = LaboUser::class;
    const NAME = 'labouser';

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof LaboUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * User exists?
     * @param string|int $value
     * @param bool $contextFilter = false
     * @return bool
     */
    public function userExists(
        string|int $value,
        bool $contextFilter = false
    ): bool
    {
        $field = filter_var($value, FILTER_VALIDATE_EMAIL) ? 'email' : 'id';
        $qb = $this->createQueryBuilder(static::NAME)
            ->select(static::NAME.'.id')
            ->andWhere(static::NAME.'.'.$field.' = :value')
            ->setParameter('value', $value)
            ;
        if($contextFilter) {
            $this->__context_Qb($qb);
        }
        $data = $qb->getQuery()->getArrayResult();
        return count($data) > 0;
    }

    /**
     * Get User as array
     * @param string|int $value
     * @param bool $contextFilter = false
     * @return array
     */
    public function findUserSimpleData(
        string|int $value,
        bool $contextFilter = false
    ): array
    {
        $field = Emails::isEmailValid($value) ? 'email' : 'id';
        $qb = $this->createQueryBuilder(static::NAME)
            ->andWhere(static::NAME.'.'.$field.' = :value')
            ->setParameter('value', $value)
            ;
        if($contextFilter) {
            $this->__context_Qb($qb);
        }
        $data = $qb->getQuery()->getArrayResult();
        return count($data) ? reset($data) : [];
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}