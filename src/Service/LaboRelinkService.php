<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\LaboRelink;
use Aequation\LaboBundle\Service\Interface\LaboRelinkServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(LaboRelinkServiceInterface::class, public: true)]
class LaboRelinkService extends ItemService implements LaboRelinkServiceInterface
{

    public const ENTITY = LaboRelink::class;

}