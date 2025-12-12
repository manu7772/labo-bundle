<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\PictureInterface;
use Aequation\LaboBundle\Repository\PictureRepository;
use Aequation\LaboBundle\Service\Interface\PictureServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: PictureRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[EA\ClassCustomService(PictureServiceInterface::class)]
class Picture extends Image implements PictureInterface
{
    public const MAPPING = 'photo';
    public const DEFAULT_LIIP_FILTER = "normal_x800";
    public const THUMBNAIL_LIIP_FILTER = 'miniature_q';

    #[Vich\UploadableField(mapping: self::MAPPING, fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname', dimensions: 'dimensions')]
    #[Assert\File(
        maxSize: '12M',
        maxSizeMessage: 'Le fichier ne peut pas dépasser la taille de {{ limit }}{{ suffix }} : votre fichier fait {{ size }}{{ suffix }}',
        mimeTypes: ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"],
        mimeTypesMessage: "Format invalide. Formats valides : JPEG, PNG, GIF, WEBP"
    )]
    #[Serializer\Ignore]
    protected ?File $file = null;

}