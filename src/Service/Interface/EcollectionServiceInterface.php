<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Doctrine\Common\Collections\Collection;

interface EcollectionServiceInterface extends ItemServiceInterface
{
    public function setEcollectionItems(EcollectionInterface $entity, array $items, ?string $field = null): EcollectionInterface;
}