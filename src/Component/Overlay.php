<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Model\Attribute\CssClasses;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Service\Tools\Strings;
use BadMethodCallException;
use JsonSerializable;
use Serializable;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class Overlay implements JsonSerializable, Serializable
{

    public const ITEMS_ATTRIBUTES = [
        'overlay'=> [
            'position' => [
                'label' => '<strong>Bloc</strong> <span style="opacity: 0.6;">position</span>',
                // 'label_html' => true, // --> automatically convert label to markup if contains html tags
                'priority' => 120,
                'type' => ChoiceType::class,
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'empty_data' => "overlay-bottom-center",
                'choices' => [
                    'Haut à gauche' => "overlay-top-left",
                    'Haut à droite' => "overlay-top-right",
                    'Haut au centre' => "overlay-top-center",
                    'Centré au milieu' => "overlay-center-center",
                    'Bas à gauche' => "overlay-bottom-left",
                    'Bas à droite' => "overlay-bottom-right",
                    'Bas au centre' => "overlay-bottom-center",
                ],
            ],
            "theme" => [
                'label' => '<strong>Bloc</strong> <span style="opacity: 0.6;">Thème</span>',
                'priority' => 115,
                'type' => ChoiceType::class,
                'required' => true,
                'empty_data' => "",
                'multiple' => false,
                'expanded' => false,
                'choices' => [
                    'Normal' => "",
                    'Rouge vif' => "theme-red",
                    'Bleu cyan' => "theme-cyan",
                    'Vert foncé' => "theme-green",
                    'Vert vif' => "theme-lime",
                    'Orange' => "theme-orange",
                    'Violet' => "theme-purple",
                    'Ambre' => "theme-amber",
                ],
            ],
            // 'style' => [
            //     'label' => '<strong>Bloc</strong> <span style="opacity: 0.6;">style</span>',
            //     'priority' => 110,
            //     'type' => ChoiceType::class,
            //     'required' => true,
            //     'multiple' => false,
            //     'expanded' => false,
            //     'empty_data' => "bg-black/50",
            //     'choices' => [
            //         'Sombre' => "bg-black/50",
            //         'Clair' => "bg-white/50",
            //         'Transparent' => "bg-transparent",
            //     ],
            // ],
            'width' => [
                'label' => '<strong>Bloc</strong> <span style="opacity: 0.6;">taille</span>',
                'priority' => 105,
                'type' => ChoiceType::class,
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'empty_data' => "",
                'choices' => [
                    '40%' => "overlay-xs",
                    '40% max' => "overlay-max-xs",
                    '60%' => "overlay-sm",
                    '60% max' => "overlay-max-sm",
                    '70%' => "overlay-md",
                    '70% max' => "",
                    '80%' => "overlay-lg",
                    '80% max' => "overlay-max-lg",
                    '95%' => "overlay-xl",
                    '95% max' => "overlay-max-xl",
                ],
            ],
        ],
        'title' => [
            'text' => [
                'label' => '<strong>Titre</strong> <span style="opacity: 0.6;">texte</span>',
                'priority' => 100,
                'type' => TextType::class,
                'required' => false,
            ],
            'size' => [
                'label' => '<strong>Titre</strong> <span style="opacity: 0.6;">taille</span>',
                'priority' => 90,
                'type' => ChoiceType::class,
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'empty_data' => "text-md",
                'choices' => [
                    "Petit" => "text-lg",
                    "Moyen" => "text-xl",
                    "Grand" => "text-2xl",
                    "Très grand" => "text-3xl",
                ],
            ],
            'style' => [
                'label' => '<strong>Titre</strong> <span style="opacity: 0.6;">style</span>',
                'priority' => 80,
                'type' => ChoiceType::class,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choices' => [
                    "Gras" => "font-bold",
                    "Italique" => "italic",
                    "Souligné" => "underline",
                ],
            ],
            'align' => [
                'label' => '<strong>Titre</strong> <span style="opacity: 0.6;">alignement</span>',
                'priority' => 70,
                'type' => ChoiceType::class,
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'empty_data' => "text-center",
                'choices' => [
                    "À gauche" => "text-left",
                    "Centré" => "text-center",
                    "À droite" => "text-right",
                    "Justifié" => "text-justify",
                ],
            ],
            'font' => [
                'label' => '<strong>Titre</strong> <span style="opacity: 0.6;">police</span>',
                'priority' => 60,
                'type' => ChoiceType::class,
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'empty_data' => "",
                'choices' => [
                    "Par défaut" => "",
                    "Manuscrit" => "cursive",
                ],
            ],
        ],
        'text' => [
            'text' => [
                'label' => '<strong>Texte</strong> <span style="opacity: 0.6;">contenu</span>',
                'priority' => 50,
                'type' => TextareaType::class,
                'required' => false,
            ],
            'size' => [
                'label' => '<strong>Texte</strong> <span style="opacity: 0.6;">taille</span>',
                'priority' => 40,
                'type' => ChoiceType::class,
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'empty_data' => "text-sm",
                'choices' => [
                    "Petit" => "text-sm",
                    "Moyen" => "text-md",
                    "Grand" => "text-lg",
                ],
            ],
            'style' => [
                'label' => '<strong>Texte</strong> <span style="opacity: 0.6;">style</span>',
                'priority' => 30,
                'type' => ChoiceType::class,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choices' => [
                    "Gras" => "font-bold",
                    "Italique" => "italic",
                    "Souligné" => "underline",
                ],
            ],
            'align' => [
                'label' => '<strong>Texte</strong> <span style="opacity: 0.6;">alignement</span>',
                'priority' => 20,
                'type' => ChoiceType::class,
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'empty_data' => "text-center",
                'choices' => [
                    "À gauche" => "text-left",
                    "Centré" => "text-center",
                    "À droite" => "text-right",
                    "Justifié" => "text-justify",
                ],
            ],
            'font' => [
                'label' => '<strong>Texte</strong> <span style="opacity: 0.6;">police</span>',
                'priority' => 10,
                'type' => ChoiceType::class,
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'empty_data' => "",
                'choices' => [
                    "Par défaut" => "",
                    "Manuscrit" => "cursive",
                ],
            ],
        ],
    ];

    protected array $data = [];

    public string $name;

    public function __construct(
        ?array $data = null
    )
    {
        $this->name = Encoders::getUniquid('overlay', '_');
        $this->initialize($data);
    }

    public function initialize(?array $data = null): void
    {
        $this->data = [];
        // default values
        foreach (static::ITEMS_ATTRIBUTES as $item => $attributes) {
            foreach ($attributes as $attribute => $value) {
                $this->data[$item][$attribute] = $value['empty_data'] ?? null;
            }
        }
        if(is_array($data)) {
            // insert data
            foreach ($data as $key => $value) {
                if(is_array($value) && array_key_exists($key, $this->data)) {
                    foreach ($value as $attribute => $attrValue) {
                        if(array_key_exists($attribute, $this->data[$key])) {
                            $method = Strings::stringFormated('set_' . $key . '_' . $attribute, 'camel');
                            $this->$method($attrValue);
                            // $this->data[$key][$attribute] = $attrValue;
                        }
                    }
                }
            }
        }
    }

    #[CssClasses(target: 'value')]
    public static function declareCss(): array
    {
        // die('declareCss method must be implemented in ' . static::class);
        $classes = [];
        foreach (static::ITEMS_ATTRIBUTES as $attributes) {
            foreach ($attributes as $options) {
                if(is_array($options['choices'] ?? null) && count($options['choices'])) {
                    $classes = array_merge($classes, array_values($options['choices']));
                }
            }
        }
        $classes = array_filter(array_unique(array_values($classes)), fn($c) => Strings::hasText($c));
        return $classes;
    }

    public function getCompiled(): array
    {
        $compiled = [];
        foreach ($this->data as $item => $attributes) {
            $compiled[$item]['text'] = null;
            $compiled[$item]['class'] = [];
            foreach ($attributes as $attribute => $value) {
                switch ($attribute) {
                    case 'text':
                        $compiled[$item]['text'] = $value;
                        break;
                    default:
                        $value = (array) $value;
                        $compiled[$item]['class'] = array_unique(array_merge($compiled[$item]['class'], $value));
                        break;
                }
            }
            $compiled[$item]['class'] = array_values(array_filter($compiled[$item]['class'], fn($c) => !empty($c)));
        }
        return $compiled;
    }

    public function __toString(): string
    {
        return $this->getTitleText() ?? $this->name;
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
        $this->initialize($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }


    /*************************************************************/
    /** CALL for data                                           **/
    /*************************************************************/

    public static function parseMethod(string $name, bool $getStatic = false): array|false
    {
        $length = $getStatic ? 4 : 3;
        $splited = preg_split('/_+/', Strings::stringFormated($name, 'snake'), $length);
        if(count($splited) === $length) {
            $parts = [
                'sgetter' => $splited[0],
                'item' => $splited[1],
                'attribute' => $splited[2],
            ];
            if($getStatic) {
                $parts['action'] = $splited[3];
            }
            return $parts;
        }
        return false;
    }

    public function __get(string $name): mixed
    {
        if($parts = static::parseMethod('get_'.$name, false)) {
            if(array_key_exists($parts['attribute'], $this->data[$parts['item'] ?? []])) {
                return $this->data[$parts['item']][$parts['attribute']];
            }
        }
        throw new BadMethodCallException("Property $name not found in " . static::class);
    }

    public function __set(string $name, mixed $value): void
    {
        if($parts = static::parseMethod('set_'.$name, false)) {
            if(array_key_exists($parts['attribute'], $this->data[$parts['item'] ?? []])) {
                $this->data[$parts['item']][$parts['attribute']] = $value;
                return;
            }
        }
        throw new BadMethodCallException("Property $name not found in " . static::class);
    }

    public function __isset($name)
    {
        if(isset($this->$name) || is_null($this->$name)) {
            return true;
        }
        if($parts = static::parseMethod('get_'.$name, false)) {
            if(array_key_exists($parts['attribute'], $this->data[$parts['item'] ?? []])) {
                return true;
            }
        }
        return false;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $getters = ['is', 'get', 'has'];
        $setters = ['set'];
        // get getter prefix
        if($parts = static::parseMethod($name, false)) {
            switch (true) {
                case in_array($parts['sgetter'], $getters):
                    // getters
                    if(array_key_exists($parts['attribute'], $this->data[$parts['item']] ?? [])) {
                        return $this->data[$parts['item']][$parts['attribute']];
                    }
                    break;
                case in_array($parts['sgetter'], $setters):
                    // setters
                    if(array_key_exists($parts['attribute'], $this->data[$parts['item']] ?? [])) {
                        if(count($arguments)) {
                            return $this->data[$parts['item']][$parts['attribute']] = reset($arguments);
                        }
                        throw new BadMethodCallException("Method $name needs one argument in " . static::class) ;
                    }
                    break;
                default:
                    // not supported method
                    break;
            }
        }
        throw new BadMethodCallException("Method $name not supported in " . static::class) ;
    }

    public static function __callStatic(string $method, array $args): mixed
    {
        $getters = ['is', 'get', 'has'];
        // $setters = ['set'];
        if(($parts = static::parseMethod($method, true)) && in_array($parts['sgetter'], $getters)) {
            // static getters
            if(array_key_exists($parts['action'], static::ITEMS_ATTRIBUTES[$parts['item']][$parts['attribute'] ?? []])) {
                return static::ITEMS_ATTRIBUTES[$parts['item']][$parts['attribute']][$parts['action']];
            }
            
        }
        throw new BadMethodCallException("Method $method not supported in " . static::class) ;
    }


    /*************************************************************/
    /** CALL for data                                           **/
    /*************************************************************/

    public static function buildForm(FormBuilderInterface $builder, array $options): FormBuilderInterface
    {
        foreach (static::ITEMS_ATTRIBUTES as $item => $attributes) {
            foreach ($attributes as $attribute => $opts) {
                $fieldName = $item . '_' . $attribute;
                $fieldType = $opts['type'];
                $fieldOptions = [];
                foreach ($opts as $optionKey => $optionValue) {
                    switch (true) {
                        case $optionKey === 'type':
                            // already used as field type
                            break;
                        case Strings::isHtml($optionValue):
                            $fieldOptions[$optionKey] = Strings::markup($optionValue);
                            $fieldOptions['label_html'] = true;
                            break;
                        default:
                            $fieldOptions[$optionKey] = $optionValue;
                            break;
                    }
                }
                $builder->add($fieldName, $fieldType, $fieldOptions);
            }
        }
        return $builder;
    }

}