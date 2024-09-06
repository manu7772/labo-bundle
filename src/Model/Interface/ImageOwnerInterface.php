<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Entity\Image;

interface ImageOwnerInterface extends CreatedInterface
{

    // public function removeOwnedImage(Image $image): static;
    public function getFirstImage(): ?Image;
    public function onDeleteFirstImage(): static;

}