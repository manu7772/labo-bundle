<?php
namespace Aequation\LaboBundle\Model\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait Slug
{

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le Slug est vide !')]
    #[Serializer\Groups(['index'])]
    protected ?string $slug = null;

    protected ?bool $updateSlug = null;

    public function __construct_slug(): void
    {
        $this->slug = '-';
        $this->updateSlug = false;
    }

    public function __clone_slug(): void
    {
        $this->slug = '-';
        $this->updateSlug = true;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function setUpdateSlug(bool $updateSlug): static
    {
        $this->updateSlug = $updateSlug;
        if($this->isUpdateSlug()) $this->setUpdatedAt();
        return $this;
    }

    public function isUpdateSlug(): bool
    {
        return $this->updateSlug || $this->slug === '-' || empty($this->slug);
    }

}