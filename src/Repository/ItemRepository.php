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

    public function findItems(
        bool $scalar = true
    ): array
    {
        $qb = $this->createQueryBuilder(static::NAME);
        $qb->orderBy(static::NAME.'.shortname', 'ASC');

        if($scalar) {
            $qb->select(static::NAME.'.id, '.static::NAME.'.shortname, '.static::NAME.'.classname, '.static::NAME.'.name, '.static::NAME.'.enabled, '.static::NAME.'.softdeleted');
            $result = $qb->getQuery()->getScalarResult();
            array_walk($result, function (&$item) {
                $item['id'] = (int)$item['id'];
                $item['enabled'] = (bool)$item['enabled'];
                $item['softdeleted'] = (bool)$item['softdeleted'];
                $item['active'] = (!$item['softdeleted'] && $item['enabled']);
            });
        } else {
            $result = $qb->getQuery()->getResult();
        }
        return $result;
    }

}
