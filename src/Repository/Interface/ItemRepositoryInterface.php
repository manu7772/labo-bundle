<?php
namespace Aequation\LaboBundle\Repository\Interface;

use Aequation\LaboBundle\Model\Interface\HasOrderedInterface;
use Doctrine\ORM\QueryBuilder;

interface ItemRepositoryInterface extends CommonReposInterface
{
    public function findItems(bool $scalar = true): array;
}
