<?php
namespace Aequation\LaboBundle\Component;

use Serializable;
use JsonSerializable;
//Symfony
use BadMethodCallException;
use Aequation\LaboBundle\Component\Nothing;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Tools\Encoders;
// PHP
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class Overlay implements JsonSerializable, Serializable
{

    public const ITEMS_ATTRIBUTES = [
        'overlay'=> [
            'position' => [
                'label' => 'Bloc > position',
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
            'style' => [
                'label' => 'Bloc > style',
                'priority' => 110,
                'type' => ChoiceType::class,
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'empty_data' => "bg-black/50",
                'choices' => [
                    'Sombre' => "bg-black/50",
                    'Clair' => "bg-white/50",
                    'Transparent' => "bg-transparent",
                ],
            ],
        ],
        'title' => [
            'text' => [
                'label' => 'Titre > texte',
                'priority' => 100,
                'type' => TextType::class,
                'required' => false,
            ],
            'size' => [
                'label' => 'Titre > taille',
                'priority' => 90,
                'type' => ChoiceType::class,
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'empty_data' => "text-md",
                'choices' => [
                    "Moyen" => "text-md",
                    "Grand" => "text-lg",
                    "Très grand" => "text-xl",
                ],
            ],
            'style' => [
                'label' => 'Titre > style',
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
                'label' => 'Titre > alignement',
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
                'label' => 'Titre > police',
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
                'label' => 'Texte > contenu',
                'priority' => 50,
                'type' => TextareaType::class,
                'required' => false,
            ],
            'size' => [
                'label' => 'Texte > taille',
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
                'label' => 'Texte > style',
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
                'label' => 'Texte > alignement',
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
                'label' => 'Texte > police',
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

    // #[CssClasses(target: 'value')]
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


    // public function getAvailableNames(): array
    // {
    //     if(!isset($this->availableNames)) {
    //         $availableNames = [];
    //         foreach (array_keys(static::ITEMS_ATTRIBUTES) as $key) {
    //             foreach (array_keys(static::ITEMS_ATTRIBUTES[$key]) as $attribute) {
    //                 $availableNames[] = $key . '_' . $attribute;
    //                 $availableNames[] = Strings::stringFormated($key . '_' . $attribute, 'camel');
    //             }
    //         }
    //         $this->availableNames = $availableNames;
    //     }
    //     return $this->availableNames;
    // }

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
        dump($name, $parts, $this->data);
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
        dump($name, $value, $parts, $this->data);
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
            dump($name, $parts, $arguments);
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
        dump($name, $arguments);
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
        // // get getter prefix
        // if(preg_match('/^('.implode('|', $getters).')(.+)$/', $method, $matches_1)) {
        //     $items = array_keys(static::ITEMS_ATTRIBUTES);
        //     if(count($matches_1) === 3 && preg_match('/^('.implode('|', $items).')(.+)$/i', $matches_1[2], $matches_2)) {
        //         $item = lcfirst($matches_2[1]);
        //         $attrs = array_keys(static::ITEMS_ATTRIBUTES[$item] ?? []);
        //         if(count($attrs) && count($matches_2) === 3 && preg_match('/^('.implode('|', $attrs).')(.+)$/i', $matches_2[2], $matches_3)) {
        //             $attribute = lcfirst($matches_3[1]);
        //             $action = lcfirst($matches_3[2]);
        //             $actions = array_keys(array_filter(static::ITEMS_ATTRIBUTES[$item][$attribute] ?? [], fn(string $k) => preg_match('/^(?!_)/', $k), ARRAY_FILTER_USE_KEY));
        //             if(count($actions) && count($matches_3) === 3) {
        //                 // dump($item, $attribute, $action);
        //                 switch (true) {
        //                     // case $action === 'choices':
        //                     //     return static::ITEMS_ATTRIBUTES[$item][$attribute]['_values'] ?? [];
        //                     //     break;
        //                     case in_array($action, $actions):
        //                         return static::ITEMS_ATTRIBUTES[$item][$attribute][$action];
        //                         break;
        //                     default:
        //                         // not supported action
        //                         break;
        //                 }
        //             }
        //         }
        //     }
        // }
        throw new BadMethodCallException("Method $method not supported in " . static::class) ;
    }


    /*************************************************************/
    /** CALL for data                                           **/
    /*************************************************************/

    public static function buildForm(FormBuilderInterface $builder, array $options): FormBuilderInterface
    {
        foreach (static::ITEMS_ATTRIBUTES as $item => $attributes) {
            foreach ($attributes as $attribute => $options) {
                $fieldName = $item . '_' . $attribute;
                $fieldType = $options['type'];
                $fieldOptions = [];
                foreach ($options as $optionKey => $optionValue) {
                    if(!in_array($optionKey, ['type'])) {
                        $fieldOptions[$optionKey] = $optionValue;
                    }
                }
                $builder->add($fieldName, $fieldType, $fieldOptions);
            }
        }
        return $builder;
    }

}