<?php
namespace Aequation\LaboBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Aequation\LaboBundle\Model\Trait\Slug;
use Aequation\LaboBundle\Service\Tools\Files;
use Aequation\LaboBundle\Model\Attribute as EA;
use Symfony\Component\HttpFoundation\File\File;
use Aequation\LaboBundle\Model\Attribute\Slugable;
use Aequation\LaboBundle\Repository\LaboArticleRepository;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Aequation\LaboBundle\Model\Interface\LaboArticleInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute as Serializer;
use Aequation\LaboBundle\Model\Interface\LaboArticleizableInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Aequation\LaboBundle\Service\Interface\LaboArticleServiceInterface;
use DateTimeInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: LaboArticleRepository::class)]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[EA\ClassCustomService(LaboArticleServiceInterface::class)]
#[Vich\Uploadable]
#[Slugable('name')]
abstract class LaboArticle extends Item implements LaboArticleInterface
{

    use Slug;

    public const ICON = "tabler:article";
    public const FA_ICON = "fa-regular fa-newspaper";


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column]
    protected array $content = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $end = null;


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): static
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

}
