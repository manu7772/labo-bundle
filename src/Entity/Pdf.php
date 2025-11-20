<?php
namespace Aequation\LaboBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Aequation\LaboBundle\Model\Trait\Slug;
use Aequation\LaboBundle\Service\Tools\Files;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Service\Tools\Strings;
use Symfony\Component\HttpFoundation\File\File;
use Aequation\LaboBundle\Model\Attribute\Slugable;
use Aequation\LaboBundle\Repository\PdfRepository;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Aequation\LaboBundle\Model\Attribute\HtmlContent;
use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute as Serializer;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\PdfizableInterface;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Aequation\LaboBundle\Service\Interface\PdfServiceInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PdfRepository::class)]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[EA\ClassCustomService(PdfServiceInterface::class)]
#[Vich\Uploadable]
#[Slugable('name')]
class Pdf extends Item implements PdfInterface, ImageOwnerInterface
{

    use Slug;

    public const ICON = 'tabler:file-type-pdf';
    public const FA_ICON = 'file-pdf';

    public const PAPERS = ['A4', 'A5', 'A6', 'letter', 'legal'];
    public const ORIENTATIONS = ['portrait', 'landscape'];
    public const SOURCETYPES = ['undefined', 'document', 'file'];

    #[ORM\Column(type: Types::INTEGER)]
    protected int $sourcetype = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[HtmlContent]
    protected ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[HtmlContent]
    protected ?string $content = null;

    // #[Assert\NotNull(message: 'Le nom de fichier ne peut être null')]
    #[ORM\Column(length: 255)]
    protected ?string $filename = null;

    #[Vich\UploadableField(mapping: 'pdf', fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname')]
    #[Assert\File(
        maxSize: '32M',
        maxSizeMessage: 'Le fichier ne peut pas dépasser la taille de {{ limit }}{{ suffix }} : votre fichier fait {{ size }}{{ suffix }}',
        mimeTypes: ["application/pdf"],
        mimeTypesMessage: "Format invalide, vous devez indiquer un fichier PDF",
        // binaryFormat: false,
    )]
    #[Serializer\Ignore]
    protected ?File $file = null;

    #[ORM\Column]
    protected ?int $size = null;

    #[ORM\Column(length: 255)]
    protected ?string $mime = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $originalname = null;

    #[ORM\Column(length: 32)]
    protected ?string $paper = 'A4';

    #[ORM\Column(length: 32)]
    protected ?string $orientation = 'portrait';

    #[ORM\Column]
    protected bool $selection = false;

    #[ORM\ManyToOne(inversedBy: 'pdfiles')]
    private ?Item $pdfowner = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid()]
    #[Serializer\Ignore]
    protected ?Photo $photo = null;


    public function __toString(): string
    {
        return $this->name ?: $this->filename ?: parent::__toString();
    }

    public function __clone()
    {
        parent::__clone();
        $this->photo = null;
    }

    public function isPdfExportable(): bool
    {
        return $this->isActive();
    }

    public function getSourcetype(): int
    {
        return $this->sourcetype;
    }

    public function getSourcetypeName(): string
    {
        return static::SOURCETYPES[$this->sourcetype] ?? 'undefined';
    }

    public function setSourcetype(int|string $sourcetype): static
    {
        // Can not change sourcetype if already set
        // if(!empty($this->getId())) return $this;

        $sourcetypes = static::SOURCETYPES;
        if(in_array($sourcetype, $sourcetypes)) {
            // got name
            $this->sourcetype = array_search($sourcetype, $sourcetypes);
        } else if(array_key_exists($sourcetype, $sourcetypes)) {
            // got key
            $this->sourcetype = $sourcetype;
        } else {
            // default
            $this->sourcetype = reset($sourcetypes);
        }
        return $this;
    }

    public static function getSourcetypeChoices(): array
    {
        return array_flip(static::SOURCETYPES);
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     * @see https://github.com/dustin10/VichUploaderBundle/blob/master/docs/usage.md
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setFile(
        File $file
    ): static
    {
        if($file = $file instanceof UploadedFile ? $file : $this->_service->getAppService()->get('Tool:Files')->getCopiedTmpFile($file)) {
            $this->file = $file;
            if($this->file instanceof UploadedFile) {
                if(!empty($this->getUpdatedAt())) $this->updateUpdatedAt();
                if(empty($this->filename)) $this->setFilename($this->file->getClientOriginalName());
                $this->updateName();
            }
        }
        return $this;
    }

    #[Serializer\Ignore]
    public function getFile(): File|null
    {
        return $this->file;
    }

    public function getFilepathname(
        $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL,
    ): ?string
    {
        return $this->_appManaged->manager->getBrowserPath($this, $filter, $runtimeConfig, $resolver, $referenceType);
    }

    public function getPdfUrlAccess(
        ?int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL,
        string $action = 'inline',
    ): ?string
    {
        return $this->_appManaged->manager->getAppService()->get('router')->generate('output_pdf_action', ['action' => $action, 'pdf' => $this->getSlug()], $referenceType ?? UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function updateName(): static
    {
        if(Strings::hasText($this->name) && !Strings::hasText($this->filename)) $this->setName($this->filename);
        return $this;
    }

    public function getFilename(
        bool|DateTimeInterface $versioned = false
    ): ?string
    {
        $date = null;
        if($versioned instanceof DateTimeInterface) {
            $date = $versioned;
        } else if($versioned ) {
            $date = $this->getUpdatedAt() ?? $this->getCreatedAt();
        }
        if($date) {
            $filename = preg_replace('/((\.pdf)+)$/i', '', $this->filename);
            return $filename.'_v'.$date->format('Ymd-His').'.pdf';
        }
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;
        $this->updateName();
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(?string $mime): static
    {
        $this->mime = $mime;
        return $this;
    }

    public function getOriginalname(): ?string
    {
        return $this->originalname;
    }

    public function setOriginalname(?string $originalname): static
    {
        $this->originalname = $originalname;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getPaper(): ?string
    {
        return $this->paper;
    }

    public function setPaper(?string $paper): static
    {
        $papers = static::PAPERS;
        $this->paper = in_array($paper, static::PAPERS) ? $paper : reset($papers);
        return $this;
    }

    public static function getPaperChoices(): array
    {
        return array_combine(static::PAPERS, static::PAPERS);
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function setOrientation(?string $orientation): static
    {
        $orients = static::ORIENTATIONS;
        $this->orientation = in_array($orientation, $orients) ? $orientation : reset($orients);
        return $this;
    }

    public static function getOrientationChoices(): array
    {
        return array_combine(static::ORIENTATIONS, static::ORIENTATIONS);
    }

    public function isSelection(): bool
    {
        return $this->selection;
    }

    public function setSelection(bool $selection): static
    {
        $this->selection = $selection;
        return $this;
    }

    public function getPdfowner(): ?Item
    {
        return $this->pdfowner;
    }

    public function setPdfowner(?Item $pdfowner): static
    {
        $this->pdfowner = $pdfowner;
        return $this;
    }


    /**********************************************************************************************
     * PHOTO
     **********************************************************************************************/

    public function removeOwnedImage(Image $photo): static
    {
        return $this->photo === $photo
            ? $this->removePhoto()
            : $this;
    }

    #[Serializer\MaxDepth(1)]
    public function getFirstImage(): ?Image
    {
        return $this->photo;
    }

    #[Serializer\MaxDepth(1)]
    public function getPhoto(): ?Photo
    {
        return $this->photo;
    }

    public function setPhoto(Photo $photo): static
    {
        if(!empty($photo->getFile())) {
            $this->removePhoto();
            $this->photo = $photo;
        }
        return $this;
    }

    public function removePhoto(): static
    {
        // if($this->photo instanceof Photo) {
        //     $photo = $this->photo;
            $this->photo = null;
        //     $photo->removeLinkedto();
        // }
        return $this;
    }

    #[AppEvent(groups: [AppEvent::POST_SUBMIT])]
    public function onDeleteFirstImage(): static
    {
        if($this->photo instanceof Image && $this->photo->isDeleteImage()) {
            $this->removePhoto();
        }
        return $this;
    }

}
