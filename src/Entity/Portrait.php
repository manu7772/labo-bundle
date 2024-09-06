<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
use Aequation\LaboBundle\Model\Interface\PortraitInterface;
use Aequation\LaboBundle\Repository\PortraitRepository;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\PortraitServiceInterface;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: PortraitRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[EA\ClassCustomService(PortraitServiceInterface::class)]
class Portrait extends Image implements PortraitInterface
{

    public const ICON = "tabler:user-square-rounded";
    public const FA_ICON = "camera";

    #[Serializer\Ignore]
    public readonly PortraitServiceInterface|AppEntityManagerInterface $_service;

    #[Vich\UploadableField(mapping: 'portrait', fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname', dimensions: 'dimensions')]
    #[Serializer\Ignore]
    protected ?File $file = null;

    // #[ORM\OneToOne(inversedBy: 'portrait')]
    // #[Serializer\Ignore]
    // protected ?LaboUser $linkedto = null;

    // public function getLinkedto(): ?ImageOwnerInterface
    // {
    //     return $this->linkedto;
    // }

    // public function setLinkedto(?ImageOwnerInterface $linkedto): static
    // {
    //     $this->linkedto = $linkedto;
    //     return $this;
    // }

    // public function getMainattached(): ?LaboUser
    // {
    //     return $this->linkedto;
    // }

    // public function setMainattached(?LaboUser $mainattached): static
    // {
    //     return $this->setLinkedto($mainattached);
    // }

    // public function getAttached(): ?LaboUser
    // {
    //     return $this->linkedto;
    // }

    // public function setAttached(?LaboUser $attached): static
    // {
    //     return $this->setLinkedto($attached);
    // }

}