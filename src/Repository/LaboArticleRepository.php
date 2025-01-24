<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\LaboArticle;
use Aequation\LaboBundle\Repository\Interface\LaboArticleRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ItemRepository<LaboArticle>
 *
 * @method LaboArticle|null find($id, $lockMode = null, $lockVersion = null)
 * @method LaboArticle|null findOneBy(array $criteria, array $orderBy = null)
 * @method LaboArticle[]    findAll()
 * @method LaboArticle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[AsAlias(LaboArticleRepositoryInterface::class, public: true)]
class LaboArticleRepository extends ItemRepository
{
    
    public const ENTITY = LaboArticle::class;

}