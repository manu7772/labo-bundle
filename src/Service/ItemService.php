<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Service\Interface\ItemServiceInterface;
use Aequation\LaboBundle\Service\AppEntityManager;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ItemServiceInterface::class, public: true)]
class ItemService extends AppEntityManager implements ItemServiceInterface
{
    public const ENTITY = Item::class;

}