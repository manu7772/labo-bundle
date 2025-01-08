<?php
namespace Aequation\LaboBundle\Model\Interface;

use App\Entity\Category;

use Aequation\LaboBundle\Component\TwigfileMetadata;
use Doctrine\Common\Collections\Collection;

interface WebsectionInterface extends ItemInterface, CreatedInterface, EnabledInterface
{

    public function getTwigfileChoices(): array;
    public function getTwigfileName(): ?string;
    public function getTwigfile(): ?string;
    public function setTwigfile(string $twigfile): static;
    public function getContent(): ?string;
    public function setContent(?string $content): static;
    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
    public function getCategorys(): Collection;
    public function addCategory(Category $category): static;
    public function removeCategory(Category $category): static;
    public function removeCategorys(): static;
    public function getMainmenu(): ?MenuInterface;
    public function setMainmenu(?MenuInterface $mainmenu): static;
    public function getTwigfileMetadata(): TwigfileMetadata;
    public function getSectiontype(): string;
    public function setSectiontype(string $sectiontype): static;
}

