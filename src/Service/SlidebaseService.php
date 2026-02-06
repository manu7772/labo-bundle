<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\LaboSlidebase;
use Aequation\LaboBundle\Service\ImageService;
use Aequation\LaboBundle\Service\Interface\SlidebaseServiceInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SlidebaseServiceInterface::class, public: true)]
class SlidebaseService extends ImageService implements SlidebaseServiceInterface
{
    public const ENTITY = LaboSlidebase::class;
}