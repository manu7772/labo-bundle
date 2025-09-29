<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Picture;
use Aequation\LaboBundle\Service\Interface\PictureServiceInterface;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PictureServiceInterface::class, public: true)]
class PictureService extends ImageService implements PictureServiceInterface
{
    public const ENTITY = Picture::class;

}