<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Service\Tools\Strings;
use JsonSerializable;
use ReflectionClass;
use Serializable;

class Overlay implements JsonSerializable, Serializable
{

    public const TITLE_CLASSES = [
        'size' => [
            'type' => 'select',
            'multiple' => false,
            "Très grand" => "xl",
            "Grand" => "lg",
            "Moyen" => "md",
        ],
        'style' => [
            'type' => 'select',
            'multiple' => true,
            "Gras" => "font-bold",
            "Italique" => "italic",
            "Souligné" => "underline",
        ],
        'align' => [
            'type' => 'select',
            'multiple' => false,
            "À gauche" => "text-left",
            "Centré" => "text-center",
            "À droite" => "text-right",
            "Justifié" => "text-justify",
            ],
        'font' => [
            'type' => 'select',
            'multiple' => false,
            "Par défaut" => "",
            "Manuscrit" => "cursive",
        ],
    ];

    public const TEXT_CLASSES = [
        'size' => [
            'type' => 'select',
            'multiple' => false,
            'values' => [
                "Très grand" => "xl",
                "Grand" => "lg",
                "Moyen" => "md",
            ],
        ],
        'style' => [
            'type' => 'select',
            'multiple' => true,
            'values' => [
                "Gras" => "font-bold",
                "Italique" => "italic",
                "Souligné" => "underline",
            ],
        ],
        'align' => [
            'type' => 'select',
            'multiple' => false,
            'values' => [
                "À gauche" => "text-left",
                "Centré" => "text-center",
                "À droite" => "text-right",
                "Justifié" => "text-justify",
            ],
        ],
        'font' => [
            'type' => 'select',
            'multiple' => false,
            'values' => [
                "Par défaut" => "",
                "Manuscrit" => "cursive",
            ],
        ],
    ];
    public const OVERLAY_POSITIONS = [
        'Haut à gauche' => "overlay-top-left",
        'Haut à droite' => "overlay-top-right",
        'Haut au centre' => "overlay-top-center",
        'Centré au milieu' => "overlay-center-center",
        'Bas à gauche' => "overlay-bottom-left",
        'Bas à droite' => "overlay-bottom-right",
        'Bas au centre' => "overlay-bottom-center",
    ];

    public string $name;
    // Overlay
    protected array $overlay_classes = [];
    protected string $position;
    // Title
    protected ?string $title = null;
    protected ?array $title_classes = null;
    // Text
    protected ?string $text = null;
    protected ?array $text_classes = null;

    public function __construct(
        ?array $data = null
    )
    {
        $this->position = static::OVERLAY_POSITIONS['Bas au centre'];
        if(empty($data)) {
            $this->name = Encoders::geUniquid('overlay', '_');
        } else {
            foreach ($data as $key => $value) {
                $this->$key = is_array($value) ? array_values($value) : $value;
            }
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function serialize(): ?string
    {
        return json_encode($this->toArray());
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function unserialize(string $data)
    {
        return new Overlay(json_decode($data, false));
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function toArray(): array
    {
        $data = [];
        $rc = new ReflectionClass(static::class);
        foreach ($rc->getProperties() as $prop) {
            $name = $prop->name;
            switch ($name) {
                // case 'title':
                //     $data[$name] = $this->getTitle(true);
                //     break;
                // case 'text':
                //     $data[$name] = $this->getText(true);
                //     break;
                default:
                    $data[$name] = $this->$name;
                    break;
            }
        }
        return $data;
    }

    public function getPosition(): string
    {
        return $this->position ??= static::OVERLAY_POSITIONS['Bas au centre'];
    }

    public function setPosition(
        string $position
    ): static
    {
        $this->position = $position;
        return $this;
    }

    public static function getPositionChoices(): array
    {
        return static::OVERLAY_POSITIONS;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(bool $withHtml = false): ?string
    {
        if(!Strings::hasText($this->title)) {
            return null;
        }
        return $withHtml ? nl2br($this->title) : $this->title;
    }

    public function setTitleClasses(
        array $title_classes
    ): static
    {
        $this->title_classes = array_values($title_classes);
        return $this;
    }

    public function getTitleClasses(): array
    {
        return array_values($this->title_classes ?? []);
    }

    public static function getTitleClassesChoices(): array
    {
        return static::TITLE_CLASSES;
    }

    public function hasTitle(): bool
    {
        return Strings::hasText((string)$this->title);
    }

    public function setText(?string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function getText(bool $withHtml = false): ?string
    {
        if(!Strings::hasText($this->text)) {
            return null;
        }
        return $withHtml ? nl2br($this->text) : $this->text;
    }

    public function setTextClasses(
        array $text_classes
    ): static
    {
        $this->text_classes = $text_classes;
        return $this;
    }

    public function getTextClasses(): array
    {
        return $this->text_classes ?? [];
    }

    public static function getTextClassesChoices(): array
    {
        return static::TEXT_CLASSES;
    }

    public function hasTtext(): bool
    {
        return Strings::hasText($this->text);
    }

}