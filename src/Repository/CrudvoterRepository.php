<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\Crudvoter;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\CrudvoterRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends CommonRepos<Crudvoter>
 *
 * @method Crudvoter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Crudvoter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Crudvoter[]    findAll()
 * @method Crudvoter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[AsAlias(CrudvoterRepositoryInterface::class, public: true)]
class CrudvoterRepository extends CommonRepos implements CrudvoterRepositoryInterface
{
    const ENTITY_CLASS = Crudvoter::class;
    const NAME = 'crudvoter';

}