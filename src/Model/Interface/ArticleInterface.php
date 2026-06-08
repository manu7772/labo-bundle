<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Model\Final\FinalCategoryInterface;
use Doctrine\Common\Collections\Collection;

interface ArticleInterface extends ItemInterface, CreatedInterface, EnabledInterface
{

    public function getContent(): ?string;
    public function setContent(?string $content): static;
    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
    public function getCategorys(): Collection;
    public function addCategory(FinalCategoryInterface $category): static;
    public function removeCategory(FinalCategoryInterface $category): static;
    public function removeCategorys(): static;
    public function getSlider(): ?SliderInterface;
    public function setSlider(?SliderInterface $slider): static;
}

