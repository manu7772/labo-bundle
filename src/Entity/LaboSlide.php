<?php
namespace Aequation\LaboBundle\Entity;

Use App\Entity\Slider;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Model\Trait\Slug;
use Aequation\LaboBundle\Component\Overlay;
use Aequation\LaboBundle\Model\Attribute\CssClasses;
use Aequation\LaboBundle\Model\Attribute\HtmlContent;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Interface\SlideInterface;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
use Aequation\LaboBundle\Model\Final\FinalLaboSlidebaseInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute as Serializer;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

abstract class LaboSlide extends Image implements SlideInterface, SlugInterface, ImageOwnerInterface
{

    use Slug;

    public const ICON = "tabler:photo-share";
    public const FA_ICON = "camera";
    public const DEFAULT_LIIP_FILTER = "#^landscape$#";
    public const SLIDE_TYPES = [
        'default' => [
            'description' => 'Diaporama panoramique, sur toute la largeur de l\'écran, format 1280x900 pixels',
            'max_slidebases' => 0,
            'overlays' => true,
            'liip_filter' => "#^landscape$#",
        ],
        'classic' => [
            'description' => 'Diaporama classique, format 800x600 pixels',
            'max_slidebases' => 0,
            'overlays' => false,
            'liip_filter' => "#^photo_pano$#",
        ],
        'beforafter' => [
            'description' => 'Diaporama avec images doubles (avant/après), format 800x600 pixels',
            'max_slidebases' => 1,
            'overlays' => false,
            'liip_filter' => "#^photo_pano$#",
        ],
    ];
    public const THUMBNAIL_LIIP_FILTER = 'tiny_q'; // miniature_q

    public const SLIDE_CLASSES = [
        "Sépia" => "sepia",
        "Noir & blanc" => "grayscale",
        "Négatif" => "invert",
        "Contraste 1" => "contrast-50",
        "Contraste 2" => "contrast-75",
        // "Contraste 3" => "contrast-100",
        "Contraste 4" => "contrast-125",
        // "Contraste 5" => "contrast-150",
        // "Contraste 6" => "contrast-200",
        "Flou 1" => "blur-sm",
        "Flou 2" => "blur",
        "Flou 3" => "blur-md",
        // "Flou 4" => "blur-lg",
        "Flou 5" => "blur-xl",
        // "Flou 6" => "blur-2xl",
        // "Flou 7" => "blur-3xl",
        "Lumière 1" => "brightness-50",
        // "Lumière 2" => "brightness-75",
        "Lumière 3" => "brightness-90",
        "Lumière 4" => "brightness-95",
        // "Lumière 5" => "brightness-100",
        "Lumière 6" => "brightness-105",
        // "Lumière 7" => "brightness-110",
        // "Lumière 8" => "brightness-110",
        "Lumière 9" => "brightness-125",
        // "Lumière 10" => "brightness-150",
        "Lumière 11" => "brightness-200",
        "Rotation couleur 15°" => "hue-rotate-15",
        "Rotation couleur 30°" => "hue-rotate-30",
        "Rotation couleur 60°" => "hue-rotate-60",
        "Rotation couleur 90°" => "hue-rotate-90",
        "Rotation couleur 180°" => "hue-rotate-180",
    ];

    #[Serializer\Ignore]
    public readonly AppEntityManagerInterface $_service;

    #[Vich\UploadableField(mapping: 'slide', fileNameProperty: 'filename', size: 'size', mimeType: 'mime', originalName: 'originalname', dimensions: 'dimensions')]
    #[Serializer\Ignore]
    protected ?File $file = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Serializer\Groups(['index'])]
    protected ?string $slidetype = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups(['BaSlider','rslider'])]
    #[HtmlContent]
    protected ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Serializer\Groups(['BaSlider','rslider'])]
    #[HtmlContent]
    protected ?string $content = null;

    #[Serializer\Groups(['BaSlider','rslider'])]
    protected ?string $imagefilter;

    #[ORM\OneToMany(targetEntity: FinalLaboSlidebaseInterface::class, mappedBy: 'slide', cascade: ['persist','remove'], orphanRemoval: true)]
    // #[Serializer\MaxDepth(1)]
    // #[Serializer\Groups(['BaSlider'])]
    // #[Serializer\Ignore]
    protected Collection $slidebases;

    #[ORM\Column]
    #[Serializer\Groups(['rslider'])]
    protected array $overlays = [];

    #[ORM\Column]
    #[Serializer\Groups(['rslider'])]
    protected array $classes = [];

    protected ?LaboSlider $tempParentSlider = null;

    public function __construct()
    {
        parent::__construct();
        $this->slidetype = Slider::getDefaultSlidetype();
        $this->slidebases = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '???';
    }

    public function setTempParentSlider(
        ?LaboSlider $tempParentSlider = null
    ): static
    {
        $this->tempParentSlider = $tempParentSlider;
        return $this;
    }

    public function getTempParentSlider(): ?LaboSlider
    {
        return $this->tempParentSlider;
    }

    public function getLiipFilterByTempParent(): string
    {
        $slidetype = empty($this->tempParentSlider)
            ? LaboSlider::getDefaultSlidetype()
            : $this->tempParentSlider->getAvailableSlideType();
        return $this->_service->getLiipFilterName(static::SLIDE_TYPES[$slidetype]['liip_filter']);
    }

    public function getSlidetypeChoices(
        bool $asHtml = true
    ): array
    {
        $choices = [];
        foreach (static::SLIDE_TYPES as $key => $value) {
            $description = $asHtml
                ? ' <i class="text-muted">('.$value['description'].')</i>'
                : ' ('.$value['description'].')';
            $choices[ucfirst($key).$description] = $key;
        }
        return $choices;
    }

    public function getLiipFilter(
        ?string $slidetype = null
    ): string
    {
        $slidetype ??= $this->slidetype;
        return empty($slidetype)
            ? static::DEFAULT_LIIP_FILTER
            : static::SLIDE_TYPES[$slidetype]['liip_filter'];
    }

    public function getSlidetype(): ?string
    {
        return $this->slidetype;
    }

    public function getSlidetypeAsText(): ?string
    {
        return empty($this->slidetype)
            ? null
            : ucfirst($this->slidetype).' : '.static::SLIDE_TYPES[$this->slidetype]['description'];
    }

    public function setSlidetype(?string $slidetype): static
    {
        if(empty($slidetype) || array_key_exists($slidetype, static::SLIDE_TYPES)) {
            $this->slidetype = $slidetype;
        }
        return $this;
    }

    public function removeSlidetype(): static
    {
        $this->slidetype = null;
        return $this;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return Collection<int, Slidebase>
     */
    public function getSlidebases(): Collection
    {
        return $this->slidebases;
    }

    public function addSlidebase(LaboSlidebase $slidebase): static
    {
        if(!$this->slidebases->contains($slidebase) && $this->canAddSlidebases()) {
            $this->slidebases->add($slidebase);
            $slidebase->setSlide($this);
        }
        return $this;
    }

    public function removeSlidebase(LaboSlidebase $slidebase): static
    {
        if($this->slidebases->removeElement($slidebase)) {
            if($slidebase->getSlide() === $this) $slidebase->setSlide(null);
        }
        return $this;
    }

    public function getMaxSlidebases(): int
    {
        return empty($this->slidetype)
            ? 0
            : static::SLIDE_TYPES[$this->slidetype]['max_slidebases'];
    }

    public function canAddSlidebases(): bool
    {
        return $this->slidebases->count() < $this->getMaxSlidebases();
    }

    public function hasSlidebasesOption(): bool
    {
        return $this->getMaxSlidebases() > 0;
    }

    public function removeOwnedImage(Image $photo): static
    {
        // Nothing to do here, because owned image is a Collection, not an ImageInterface
        return $this;
    }

    #[Serializer\Ignore]
    public function getFirstImage(): ?Image
    {
        return $this;
    }

    // #[AppEvent(groups: [AppEvent::POST_SUBMIT])]
    public function onDeleteFirstImage(): static
    {
        // if($this->portrait instanceof Image && $this->portrait->isDeleteImage()) {
        //     $this->removePortrait();
        // }
        return $this;
    }

    #[ORM\PostLoad]
    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    public function loadOverlays(): static
    {
        $this->overlays ??= [];
        foreach ($this->overlays as $name => $overlay) {
            if(!($overlay instanceof Overlay)) {
                if(is_string($overlay)) {
                    $overlay = json_decode($overlay, true);
                } else if(is_object($overlay)) {
                    $overlay = json_decode(json_encode($overlay), true);
                }
                $this->overlays[$name] = new Overlay($overlay);
            }
        }
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function arrayizeOverlays(): static
    {
        $this->overlays ??= [];
        foreach ($this->overlays as $name => $overlay) {
            if($overlay instanceof Overlay) {
                $this->overlays[$name] = $overlay->toArray();
            }
        }
        // remove same overlays (if any) to avoid duplicates (compare with json_encode to avoid object reference issues)
        $this->overlays = array_map("json_decode", array_unique(array_map("json_encode", $this->overlays)));
        return $this;
    }

    public function getOverlays(): array
    {
        return array_values($this->overlays ?? []);
    }

    #[Serializer\Groups(['rslider'])]
    public function getOverlaysCompiled(): array
    {
        $overlays = [];
        foreach ($this->getOverlays() as $overlay) {
            $overlays[] = $overlay->getCompiled();
        }
        return $overlays;
    }

    public function addOverlay(array|Overlay $overlay): static
    {
        $this->overlays ??= [];
        if (is_array($overlay)) {
            $overlay = new Overlay($overlay);
        }
        $this->overlays[$overlay->name] = $overlay;
        return $this;
    }

    public function removeOverlay(Overlay|string $overlay): static
    {
        $this->overlays ??= [];
        $name = $overlay instanceof Overlay ? $overlay->name : $overlay;
        if(isset($this->overlays[$name])) unset($this->overlays[$name]);
        return $this;
    }

    public function setOverlays(array $overlays): static
    {
        $this->overlays = $overlays;
        return $this->loadOverlays();
    }

    public function hasOverlaysOption(): bool
    {
        return empty($this->slidetype)
            ? false
            : static::SLIDE_TYPES[$this->slidetype]['overlays'];
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    #[CssClasses(target: 'value')]
    public static function getClassesChoices(): array
    {
        return static::SLIDE_CLASSES;
    }

    public function setClasses(?array $classes): static
    {
        $this->classes = $classes ?? [];
        return $this;
    }



}
