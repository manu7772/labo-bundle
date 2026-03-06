<?php
namespace Aequation\LaboBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Aequation\LaboBundle\Entity\Image;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
// Symfony
use Aequation\LaboBundle\Model\Interface\SlideInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Aequation\LaboBundle\Model\Interface\SlidebaseInterface;
use Aequation\LaboBundle\Model\Final\FinalLaboSlideInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;

class LaboSlidebase extends Image implements SlidebaseInterface
{

    public const ICON = "tabler:photo-share";
    public const FA_ICON = "camera";

    #[Serializer\Ignore]
    public readonly AppEntityManagerInterface $_service;

    #[Vich\UploadableField(mapping: 'slide', fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname', dimensions: 'dimensions')]
    protected ?File $file = null;

    #[ORM\ManyToOne(inversedBy: 'slidebases')]
    #[Serializer\Ignore]
    protected ?FinalLaboSlideInterface $slide = null;


    #[Serializer\Ignore]
    public function getSlide(): ?SlideInterface
    {
        return $this->slide;
    }

    public function setSlide(
        ?SlideInterface $slide
    ): static
    {
        if($slide !== $this->slide && $this->slide) {
            $this->slide->removeSlidebase($this);
        }
        $this->slide = $slide;
        if($this->slide && !$this->slide->getSlidebases()->contains($this)) {
            $this->slide->addSlidebase($this);
        }
        return $this;
    }

    // public function removeSlide(): static
    // {
    //     $slide = $this->slide;
    //     $this->slide = null;
    //     if($slide && $slide->getSlidebases()->contains($this)) {
    //         $slide->removeSlidebase($this);
    //     }
    //     return $this;
    // }

}