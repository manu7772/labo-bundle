<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
use Aequation\LaboBundle\Model\Interface\PhotoInterface;
use Aequation\LaboBundle\Repository\PhotoRepository;
use Aequation\LaboBundle\Service\Interface\PhotoServiceInterface;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[EA\ClassCustomService(PhotoServiceInterface::class)]
class Photo extends Image implements PhotoInterface
{

    #[Vich\UploadableField(mapping: 'photo', fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname', dimensions: 'dimensions')]
    #[Serializer\Ignore]
    protected ?File $file = null;

    // #[ORM\OneToOne(inversedBy: 'photo')]
    // #[Serializer\Ignore]
    // protected ?Item $linkedto = null;

    // public function getLinkedto(): ?ImageOwnerInterface
    // {
    //     return $this->linkedto;
    // }

    // public function setLinkedto(?ImageOwnerInterface $linkedto): static
    // {
    //     $this->linkedto = $linkedto;
    //     return $this;
    // }

}