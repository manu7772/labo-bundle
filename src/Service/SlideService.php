<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\LaboSlide;
use Aequation\LaboBundle\Service\ImageService;
use Aequation\LaboBundle\Service\Interface\SlideServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SlideServiceInterface::class, public: true)]
class SlideService extends ImageService implements SlideServiceInterface
{
    public const ENTITY = LaboSlide::class;

}