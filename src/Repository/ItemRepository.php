<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\ItemRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends CommonRepos<Item>
 *
 * @method Item|null find($id, $lockMode = null, $lockVersion = null)
 * @method Item|null findOneBy(array $criteria, array $orderBy = null)
 * @method Item[]    findAll()
 * @method Item[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[AsAlias(ItemRepositoryInterface::class, public: true)]
class ItemRepository extends CommonRepos implements ItemRepositoryInterface
{

    const ENTITY_CLASS = Item::class;
    const NAME = 'item';


}
