<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Ecollection;
use Aequation\LaboBundle\Service\Interface\EcollectionServiceInterface;
use Aequation\LaboBundle\Service\ItemService;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(EcollectionServiceInterface::class, public: true)]
class EcollectionService extends ItemService implements EcollectionServiceInterface
{
    public const ENTITY = Ecollection::class;

}