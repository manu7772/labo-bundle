<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Model\Trait\Slug;
use Aequation\LaboBundle\Entity\Ecollection;
use Aequation\LaboBundle\Model\Attribute\HtmlContent;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Interface\SliderInterface;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
// Symfony
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute as Serializer;

abstract class LaboSlider extends Ecollection implements SliderInterface, SlugInterface, ImageOwnerInterface
{

    use Slug;

    public const ICON = "tabler:slideshow";
    public const FA_ICON = "images";
    public const ITEMS_ACCEPT = [
        'items' => [LaboSlide::class],
    ];
    public const SLIDER_TYPES = [
        'default' => [
            'description' => 'Diaporama panoramique, sur toute la largeur de l\'écran, format 1280x900 pixels',
            'slide_type' => 'default',
        ],
        'classic' => [
            'description' => 'Diaporama classique, format 800x600 pixels',
            'slide_type' => 'classic',
        ],
        'baslider' => [
            'description' => 'Diaporama avec images doubles (avant/après), format 800x600 pixels',
            'slide_type' => 'beforafter',
        ],
    ];

    #[ORM\Column(length: 16, nullable: false, updatable: false)]
    #[Serializer\Groups(['index'])]
    protected ?string $slidertype = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups(['BaSlider'])]
    #[HtmlContent]
    protected ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Serializer\Groups(['BaSlider'])]
    #[HtmlContent]
    protected ?string $content = null;

    #[Serializer\Ignore]
    public readonly AppEntityManagerInterface $_service;

    public function __construct()
    {
        parent::__construct();
        $this->slidertype = $this->getDefaultSlidertype();
    }

    /**
     * @return Collection<int, Slide>
     */
    #[Serializer\MaxDepth(1)]
    #[Serializer\Groups(['BaSlider'])]
    public function getSlides(
        bool $filterActives = false
    ): Collection
    {
        $slides = $filterActives
            ? $this->items->filter(function ($item) use ($filterActives) { return !$filterActives || $item->isActive(); })
            : $this->items;
        $slides->map(fn($slide) => $slide->setTempParentSlider($this));
        return $slides;
        // return $this->filterAcceptedItemsForEcollection($this->items, 'items');
        // return $this->items->filter(fn ($item) => $item instanceof SlideInterface);
    }

    public static function getSlidertypeChoices(
        bool $asHtml = true
    ): array
    {
        $choices = [];
        foreach (static::SLIDER_TYPES as $key => $value) {
            $description = $asHtml
                ? ' <i class="text-muted">('.$value['description'].')</i>'
                : ' ('.$value['description'].')';
            $choices[ucfirst($key).$description] = $key;
        }
        return $choices;
    }

    public static function getDefaultSlidertype(): string
    {
        return array_key_first(static::SLIDER_TYPES);
    }

    public static function getDefaultSlidetype(): string
    {
        $slidertype = array_key_first(static::SLIDER_TYPES);
        return static::SLIDER_TYPES[$slidertype]['slide_type'];
    }

    public function getSlidertype(): string
    {
        return $this->slidertype;
    }

    public function getSlidertypeAsText(): string
    {
        return ucfirst($this->slidertype).' : '.static::SLIDER_TYPES[$this->slidertype]['description'];
    }

    public function setSlidertype(string $slidertype): static
    {
        if(!$this->_appManaged->isPersisted() && array_key_exists($slidertype, static::SLIDER_TYPES)) {
            $this->slidertype = $slidertype;
        }
        return $this;
    }

    public function getAvailableSlideType(): string
    {
        return static::SLIDER_TYPES[$this->slidertype]['slide_type'];
    }

    public function isAvailableSlide(
        LaboSlide $slide
    ): bool
    {
        return empty($slide->getSlidetype()) || static::SLIDER_TYPES[$this->getSlidertype()]['slide_type'] === $slide->getSlidetype();
    }

    public function addSlide(
        LaboSlide $slide
    ): bool
    {
        if(!$this->items->contains($slide) && $this->isAvailableSlide($slide)) {
            $this->items->add($slide);
        } else if($this->items->contains($slide)) {
            $this->removeSlide($slide);
        }
        return $this->items->contains($slide);
    }

    public function removeSlide(
        LaboSlide $slide
    ): static
    {
        $this->items->removeElement($slide);
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

    public function removeOwnedImage(Image $eImage): static
    {
        // Nothing to do
        return $this;
    }

    #[Serializer\Ignore]
    public function getFirstImage(): ?Image
    {
        return $this->items->isEmpty()
            ? null
            : $this->items->first();
    }

    // #[AppEvent(groups: [AppEvent::POST_SUBMIT])]
    public function onDeleteFirstImage(): static
    {
        // if($this->portrait instanceof Image && $this->portrait->isDeleteImage()) {
        //     $this->removePortrait();
        // }
        return $this;
    }

}
