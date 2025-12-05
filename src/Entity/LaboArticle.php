<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Model\Trait\Slug;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Attribute\Slugable;
use Aequation\LaboBundle\Service\Interface\LaboArticleServiceInterface;
use Aequation\LaboBundle\Model\Attribute\HtmlContent;
use Aequation\LaboBundle\Model\Attribute\RelationOrder;
use Aequation\LaboBundle\Repository\LaboArticleRepository;
use Aequation\LaboBundle\Model\Final\FinalCategoryInterface;
use Aequation\LaboBundle\Model\Interface\LaboArticleInterface;
use Aequation\LaboBundle\Model\Trait\Screenable;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
// PHP
use DateTimeInterface;

#[ORM\Entity(repositoryClass: LaboArticleRepository::class)]
#[EA\ClassCustomService(LaboArticleServiceInterface::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[Slugable('name')]
abstract class LaboArticle extends Item implements LaboArticleInterface
{

    use Slug, Screenable;

    public const ICON = "tabler:article";
    public const FA_ICON = "fa-regular fa-newspaper";
    public const ITEMS_ACCEPT = [
        'categorys' => [FinalCategoryInterface::class],
    ];


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[HtmlContent]
    protected ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[HtmlContent]
    protected string $content;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $end = null;

    /**
     * @var Collection<int, FinalCategoryInterface>
     */
    #[ORM\ManyToMany(targetEntity: FinalCategoryInterface::class)]
    #[RelationOrder()]
    #[Serializer\Groups('detail')]
    protected Collection $categorys;


    public function __construct()
    {
        parent::__construct();
        $this->categorys = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(?\DateTimeInterface $start): static
    {
        $this->start = $start;
        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?\DateTimeInterface $end): static
    {
        $this->end = $end;
        return $this;
    }

    /**
     * @return Collection<int, FinalCategoryInterface>
     */
    public function getCategorys(): Collection
    {
        return $this->categorys;
    }

    public function addCategory(FinalCategoryInterface $category): static
    {
        if (!$this->categorys->contains($category)) {
            $this->categorys->add($category);
        }
        return $this;
    }

    public function removeCategory(FinalCategoryInterface $category): static
    {
        $this->categorys->removeElement($category);
        return $this;
    }

    public function removeCategorys(): static
    {
        foreach ($this->categorys as $category) {
            /** @var FinalCategoryInterface $category */
            $this->removeCategory($category);
        }
        return $this;
    }

}
