<?php
namespace Aequation\LaboBundle\Component;

use Serializable;
use ReflectionClass;
use JsonSerializable;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Model\Attribute\CssClasses;

class Overlay implements JsonSerializable, Serializable
{

    public const ITEMS_ATTRIBUTES = [
        'title' => [
            'size' => [
                'type' => 'select',
                'multiple' => false,
                'values' => [
                    "Très grand" => "text-xl",
                    "Grand" => "text-lg",
                    "Moyen" => "text-md",
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
        ],
        'text' => [
            'size' => [
                'type' => 'select',
                'multiple' => false,
                'values' => [
                    "Grand" => "text-lg",
                    "Moyen" => "text-md",
                    "Petit" => "text-sm",
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

    #[CssClasses(target: 'value')]
    public static function declareCss(): array
    {
        // die('declareCss method must be implemented in ' . static::class);
        $classes = [];
        foreach (static::ITEMS_ATTRIBUTES as $item => $attributes) {
            foreach ($attributes as $attribute => $options) {
                if(is_array($options['values'] ?? null) && count($options['values'])) {
                    $classes = array_merge($classes, array_values($options['values']));
                }
            }
        }
        $classes = array_unique(array_values($classes));
        die('declareCss for ' . static::class . ' : ' . implode(', ', $classes));
        return $classes;
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


    /*************************************************************/
    /** OVERLAY BLOCK                                           **/
    /*************************************************************/

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


    /*************************************************************/
    /** TITLE                                                   **/
    /*************************************************************/

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

    public function hasTitle(): bool
    {
        return Strings::hasText((string)$this->title);
    }


    /*************************************************************/
    /** TEXT                                                    **/
    /*************************************************************/

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

    public function hasText(): bool
    {
        return Strings::hasText($this->text);
    }


    /*************************************************************/
    /** CALL for data                                           **/
    /*************************************************************/

    public static function __callStatic($method, $args)
    {
        $getters = ['is', 'get', 'has'];
        $setters = ['set'];
        // get getter prefix
        if($prefix = preg_match('/^('.implode('|', array_merge($getters, $setters)).')(.+)$/', $method, $matches_1)) {
            $items = array_keys(static::ITEMS_ATTRIBUTES);
            if(count($matches_1) === 3 && $item = preg_match('/^('.implode('|', $items).')(.+)$/i', $matches_1[2], $matches_2)) {
                $item = lcfirst($matches_2[1]);
                $attrs = array_keys(static::ITEMS_ATTRIBUTES[$item] ?? []);
                if(count($attrs) && count($matches_2) === 3 && $attribute = preg_match('/^('.implode('|', $attrs).')(.+)$/i', $matches_2[2], $matches_3)) {
                    $attribute = lcfirst($matches_3[1]);
                    dd($method, $prefix, $matches_1, $item, $matches_2, $attribute, $matches_3);
                }
                    
                $action = $matches_1[1];
                $item = strtolower($matches_2[1]);
                $property = $item . '_classes';
                switch ($action) {
                    case in_array($action, $getters):
                        return static::$$property ?? null;
                    case in_array($action, $setters):
                        static::$$property = $args[0] ?? null;
                        return true;
                }
            }
        }
    }

}