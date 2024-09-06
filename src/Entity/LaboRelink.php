<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Model\Attribute\Slugable;
use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Trait\Slug;
use Aequation\LaboBundle\Repository\LaboRelinkRepository;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Service\Interface\LaboRelinkServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: LaboRelinkRepository::class)]
#[EA\ClassCustomService(LaboRelinkServiceInterface::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[Slugable('name')]
abstract class LaboRelink extends Item implements LaboRelinkInterface, SlugInterface
{

    use Slug;

    public const ICON = 'tabler:link';
    public const FA_ICON = 'link';
    /**
     * @see https://www.w3schools.com/tags/att_a_target.asp 
     * <a target="_blank|_self|_parent|_top|framename">
     */
    public const TARGETS = [
        'Même page' => '_self',
        'Nouvel onglet' => '_blank',
    ];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $url = null;

    #[ORM\Column(length: 128, nullable: true)]
    protected ?string $route = null;

    #[ORM\Column(nullable: true)]
    protected ?array $params = null;

    #[ORM\Column(length: 16, nullable: true)]
    protected ?string $target = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'relinks')]
    protected ?self $parentrelink = null;

    #[ORM\Column]
    protected bool $turboenabled = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $linktitle = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentrelink')]
    protected Collection $relinks;

    public function __construct()
    {
        parent::__construct();
        $this->relinks = new ArrayCollection();
        $targets = static::TARGETS;
        $this->target = reset($targets);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): static
    {
        $this->route = $route;
        return $this;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(?array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function getTargetChoices(): array
    {
        return static::TARGETS;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(string $target): static
    {
        $this->target = $target;
        return $this;
    }

    public function getParentrelink(): ?self
    {
        return $this->parentrelink;
    }

    public function setParentrelink(?self $parentrelink): static
    {
        $this->parentrelink = $parentrelink;
        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getrelinks(): Collection
    {
        return $this->relinks;
    }

    public function addChild(self $child): static
    {
        if (!$this->relinks->contains($child)) {
            $this->relinks->add($child);
            $child->setParentrelink($this);
        }
        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->relinks->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParentrelink() === $this) {
                $child->setParentrelink(null);
            }
        }
        return $this;
    }

    public function setTurboenabled(
        bool $turboenabled = true
    ): static
    {
        $this->turboenabled = $turboenabled;
        return $this;
    }

    public function isTurboenabled(): bool
    {
        return $this->turboenabled;
    }

    public function getLinktitle(): ?string
    {
        return $this->linktitle;
    }

    public function setLinktitle(?string $linktitle): static
    {
        $this->linktitle = $linktitle;
        return $this;
    }

    // #[ORM\PrePersist]
    // #[ORM\PreUpdate]
    // public function updateLinkTitle(): static
    // {
    //     if(empty($this->linktitle)) $this->linktitle = $this->title;
    //     $this->linktitle = trim($this->linktitle);
    //     return $this;
    // }

}
