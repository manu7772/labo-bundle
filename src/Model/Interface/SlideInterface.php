<?php
namespace Aequation\LaboBundle\Model\Interface;

// Symfony
use Doctrine\Common\Collections\Collection;
use Aequation\LaboBundle\Entity\LaboSlidebase;

interface SlideInterface extends ImageInterface
{
    public function getSlidebases(): Collection;
    public function addSlidebase(LaboSlidebase $slidebase): static;
    public function removeSlidebase(LaboSlidebase $slidebase): static;
    public function getMaxSlidebases(): int;
    public function canAddSlidebases(): bool;
    public function hasSlidebasesOption(): bool;
}

