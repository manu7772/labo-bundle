<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Service\Tools\Encoders;
use JsonSerializable;
use ReflectionClass;
use Serializable;

class Overlay implements JsonSerializable, Serializable
{

    public const TITLE_CLASSES = [
        "Taille 1" => "text-1xl",
        "Taille 2" => "text-2xl",
        "Taille 3" => "text-3xl",
        "Manuscrit" => "cursive",
        "Justify" => "text-justify",
        "Gauche" => "text-left",
        "Droite" => "text-right",
        "Centré" => "text-center",
    ];

    public const TEXT_CLASSES = [
        "Justifié" => "text-justify",
        "Gauche" => "text-left",
        "Droite" => "text-right",
        "Centré" => "text-center",
    ];

    public const OVERLAY_POSITIONS = [
        'Haut à gauche' => "overlay-top-left",
        'Haut à droite' => "overlay-top-right",
        'Haut au centre' => "overlay-top-center",
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
        array $data = null
    )
    {
        $this->position = static::OVERLAY_POSITIONS['Bas au centre'];
        if(empty($data)) {
            $this->name = Encoders::geUniquid('overlay', '_');
        } else {
            foreach ($data as $key => $value) {
                $this->$key = $value;
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
            $data[$name] = $this->$name;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitleClasses(
        array $title_classes
    ): static
    {
        $this->title_classes = $title_classes;
        return $this;
    }

    public function getTitleClasses(): array
    {
        return $this->title_classes;
    }

    public static function getTitleClassesChoices(): array
    {
        return static::TITLE_CLASSES;
    }

    public function hasTitle(): bool
    {
        return strlen(strip_tags((string)$this->title)) > 0;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
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
        return $this->text_classes;
    }

    public static function getTextClassesChoices(): array
    {
        return static::TEXT_CLASSES;
    }

    public function hasTtext(): bool
    {
        return strlen(strip_tags((string)$this->text)) > 0;
    }

}