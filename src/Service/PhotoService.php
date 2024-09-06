<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Photo;
use Aequation\LaboBundle\Service\Interface\PhotoServiceInterface;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PhotoServiceInterface::class, public: true)]
class PhotoService extends ImageService implements PhotoServiceInterface
{
    public const ENTITY = Photo::class;

}