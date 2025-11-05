<?php
namespace Aequation\LaboBundle\Entity;

use Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Aequation\LaboBundle\Service\Tools\Files;
use Aequation\LaboBundle\Model\Attribute as EA;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\FormBuilderInterface;
use Aequation\LaboBundle\Component\AppEntityInfo;

use Symfony\Component\Serializer\Attribute\Groups;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Aequation\LaboBundle\Repository\ImageRepository;
use Aequation\LaboBundle\Model\Attribute\HtmlContent;
use Symfony\Component\Validator\Constraints as Assert;
use Aequation\LaboBundle\Model\Interface\ImageInterface;

use Symfony\Component\Serializer\Attribute as Serializer;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\CreatedInterface;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Aequation\LaboBundle\Service\Interface\ImageServiceInterface;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[EA\ClassCustomService(ImageServiceInterface::class)]
#[Vich\Uploadable]
abstract class Image extends Item implements ImageInterface
{

    public const ICON = 'tabler:photo';
    public const FA_ICON = 'camera';
    public const MAPPING = 'photo';
    public const SERIALIZATION_PROPS = ['id','euid','name','file','filename','size','mime','classname','shortname'];
    public const DEFAULT_LIIP_FILTER = "normal_x800";
    public const THUMBNAIL_LIIP_FILTER = 'miniature_q';
    public const LIIP_FILTERS = [
        // 'Aucun format prédéfini' => null,
        'normal_x300',
        'normal_x800',
        'normal_x1200',
        'photo_h',
        'photo_v',
        'photo_q',
        'photo_reduced_600',
        'photo_fullscreen',
        'landscapethin',
        'landscapemin',
        'landscape',
    ];
    

    // #[Assert\NotNull(message: 'Le nom de fichier ne peut être null')]
    #[ORM\Column(length: 255)]
    protected ?string $filename = null;

    #[Vich\UploadableField(mapping: self::MAPPING, fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname', dimensions: 'dimensions')]
    #[Assert\File(
        maxSize: '12M',
        maxSizeMessage: 'Le fichier ne peut pas dépasser la taille de {{ limit }}{{ suffix }} : votre fichier fait {{ size }}{{ suffix }}',
        mimeTypes: ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"],
        mimeTypesMessage: "Format invalide. Formats valides : JPEG, PNG, GIF, WEBP"
    )]
    #[Serializer\Ignore]
    protected ?File $file = null;

    #[ORM\Column]
    protected ?int $size = null;

    #[ORM\Column(length: 255)]
    protected ?string $mime = null;

    #[ORM\Column(length: 255)]
    protected ?string $originalname = null;

    #[ORM\Column(length: 255)]
    protected ?string $dimensions = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $imagefilter;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[HtmlContent]
    protected ?string $description = null;

    protected bool $deleteImage = false;
    protected ?string $liipDefaultFilter = null;

    public function __toString(): string
    {
        return $this->name ?? $this->filename ?? parent::__toString();
    }

    public static function getLiipFilterChoices(): array
    {
        return array_combine(array_map(fn($v) => 'liip_names.'.$v, static::LIIP_FILTERS), static::LIIP_FILTERS);
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
    public function setFile(File $file): static
    {
        $this->file = HttpRequest::isCli()
        ? $this->_service->getAppService()->get('Tool:Files')->getCopiedTmpFile($file)
        : $file;
        if(!empty($this->getId())) $this->updateUpdatedAt();
        if(empty($this->filename)) $this->setFilename($this->file->getFilename());
        $this->updateName();
        return $this;
    }

    #[Serializer\Ignore]
    public function getFile(): File|null
    {
        return $this->file;
    }

    #[Serializer\Groups(['rslider'])]
    public function getFilepathname(
        $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): ?string
    {
        $filter ??= $this->getImagefilter();
        return $this->_appManaged->manager->getBrowserPath($this, $filter, $runtimeConfig, $resolver, $referenceType);
    }

    public function getLiipDefaultFilter(): string
    {
        return $this->liipDefaultFilter ??= static::DEFAULT_LIIP_FILTER;
    }

    public function setLiipDefaultFilter(string $liipDefaultFilter): static
    {
        $this->liipDefaultFilter = $liipDefaultFilter;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onPersistOrUpdate(): static
    {
        $this->updateName();
        return $this;
    }


    public function updateName(): static
    {
        if(empty($this->name) && !empty($this->filename)) $this->setName($this->filename);
        return $this;
    }

    public function getFilename(): ?string
    {
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

    public function getDimensions(bool $asArray = false): null|string|array
    {
        return $asArray
            ? explode('x', $this->dimensions)
            : $this->dimensions;
    }

    public function setDimensions(mixed $dimensions): static
    {
        $this->dimensions = is_array($dimensions)
            ? implode('x',$dimensions)
            : (string)$dimensions;
        return $this;
    }

    public function getImagefilter(): ?string
    {
        return $this->imagefilter ??= $this->getLiipDefaultFilter();
    }

    public function setImagefilter(?string $imagefilter): static
    {
        $this->imagefilter = $imagefilter;
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

    public function setDeleteImage(bool $deleteImage): static
    {
        $this->deleteImage = $deleteImage;
        $this->setUpdatedAt();
        return $this;
    }

    public function isDeleteImage(): bool
    {
        return $this->deleteImage;
    }

    #[AppEvent(groups: FormEvents::PRE_SET_DATA)]
    public function formEvent_preSetData(
        ImageServiceInterface $service,
        array $data,
        ?string $group
    ): void
    {
        $event = $data['event'] ?? null;
        if($event instanceof FormEvent) {
            /** @var Form */
            $form = $event->getForm();
            if(!$form->isRoot() && !$form->isRequired()) {
                $event->getForm()->add(child: 'deleteImage', type: CheckboxType::class, options: [
                    'label' => 'Supprimer la photo',
                    'by_reference' => false,
                ]);
            }
        }
    }

}