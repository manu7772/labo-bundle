<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\PortraitInterface;
use Aequation\LaboBundle\Repository\PortraitRepository;
use Aequation\LaboBundle\Service\Interface\PortraitServiceInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: PortraitRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[EA\ClassCustomService(PortraitServiceInterface::class)]
class Portrait extends Image implements PortraitInterface
{

    public const ICON = "tabler:user-square-rounded";
    public const FA_ICON = "camera";
    public const MAPPING = 'portrait';
    public const DEFAULT_LIIP_FILTER = "photo_reduced_600";
    public const THUMBNAIL_LIIP_FILTER = 'miniature_q';
    public const LIIP_FILTERS = [
        // 'Aucun format prédéfini' => null,
        'normal_x300',
        'normal_x800',
        'photo_h',
        'photo_v',
        'photo_q',
        'photo_reduced_600',
    ];

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