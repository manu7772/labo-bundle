<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Portrait;
use Aequation\LaboBundle\Service\Interface\PortraitServiceInterface;
use Aequation\LaboBundle\Service\ImageService;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PortraitServiceInterface::class, public: true)]
class PortraitService extends ImageService implements PortraitServiceInterface
{
    public const ENTITY = Portrait::class;
}