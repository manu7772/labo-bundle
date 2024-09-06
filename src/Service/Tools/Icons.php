<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
use Symfony\Component\String\Slugger\AsciiSlugger;

use function Symfony\Component\String\u;

class Icons extends BaseService
{

    public const DEFAULT_SIZE = '32px';
    public const TEST_TYPES = [
        '/^(ux@)/' => [ // --> IMPORTANT: this test needs to be first
            'type' => 'ux_icon_prefixed',
            'strip' => true,
        ],
        '/:/' => [
            'type' => 'ux_icon',
            'strip' => false,
        ],
    ];

    public static function getNormalizedAndIconType(
        string &$icon
    ): string
    {
        foreach (static::TEST_TYPES as $test => $type) {
            if(preg_match($test, $icon)) {
                if($type['strip']) $icon = preg_replace(is_string($type['strip']) ? $type['strip'] : $test, '', $icon);
                return $type['type'];
            }
        }
        return 'default';
    }

    public static function getIcon(
        string|object $icon,
        array|string $attributes = [],
        bool $as_array_if_ux_icon = true
    ): string|array|null
    {
        if(is_object($icon)) {
            $icon = get_class($icon);
        }
        if(class_exists($icon)) {
            $icon = $icon::ICON;
        }
        $icon_html = '';
        $type = static::getNormalizedAndIconType($icon);
        switch ($type) {
            case 'ux_icon':
            case 'ux_icon_prefixed':
                if(empty($attributes)) $attributes = static::DEFAULT_SIZE;
                if(is_string($attributes)) $attributes = ['width' => $attributes, 'height' => $attributes];
                if(isset($attributes['size'])) {
                    $attributes['width'] = is_int($attributes['size']) ? $attributes['size'].'px' : $attributes['size'];
                    $attributes['height'] = is_int($attributes['size']) ? $attributes['size'].'px' : $attributes['size'];
                    unset($attributes['size']);
                }
                if($as_array_if_ux_icon) {
                    return [
                        'icon' => $icon,
                        'attributes' => $attributes,
                    ];
                }
                $attributes = static::attributesToString($attributes);
                $icon_html = vsprintf('<twig:ux:icon name="%s"%s />', [$icon, $attributes]);
                break;
            default:
                if(is_string($attributes)) $attributes = ['class' => $attributes];
                $attributes = static::attributesToString($attributes, ['fas','fa-fw','fa-'.$icon]);
                $icon_html = vsprintf('<i%s></i>', [$attributes]);
                break;
        }
        return empty($icon_html) ? null : Strings::markup($icon_html);
    }

    public static function getValidIcon(
        bool $value = true,
        string|array $attributes = [],
        string $icon_true = 'ux@tabler:check',
        string $icon_false = 'ux@tabler:x',
        bool $as_array_if_ux_icon = true
    ) : string|array|null
    {
        return $value
            ? static::getIcon($icon_true, $attributes, $as_array_if_ux_icon)
            : static::getIcon($icon_false, $attributes, $as_array_if_ux_icon)
        ;
    }

    public static function attributesToString(
        array $attributes,
        array|string $pre_classes = [],
    ): ?string
    {
        $string = '';
        if(!empty($pre_classes)) {
            if(is_array($pre_classes)) $pre_classes = implode(' ', $pre_classes);
            $pre_classes = preg_split('/\s+/', $pre_classes, -1, PREG_SPLIT_NO_EMPTY);
        }
        $class_done = false;
        foreach ($attributes as $name => $values) {
            switch ($name) {
                case 'class':
                    if(is_array($values)) $values = implode(' ', $values);
                    $values = preg_split('/\s+/', $values, -1, PREG_SPLIT_NO_EMPTY);
                    if(!empty($values)) {
                        if(!empty($pre_classes)) {
                            $values = array_filter($values, function($value) use ($pre_classes) {
                                return !in_array($value, $pre_classes);
                            });
                            $values = array_merge($pre_classes, $values);
                            $class_done = true;
                        }
                        $string .= ' '.$name.'="'.implode(' ', $values).'"';
                    }
                    break;
                case 'style':
                    $values = static::stylesToString($values);
                    if(!empty($values)) $string .= ' '.$name.'="'.$values.'"';
                    break;
                default:
                    if(is_array($values)) $values = implode(' ', $values);
                    $values = preg_split('/\s+/', $values, -1, PREG_SPLIT_NO_EMPTY);
                    if(!empty($values)) {
                        $string .= ' '.$name.'="'.implode(' ', $values).'"';
                    }
                    break;
            }
        }
        if(!$class_done && !empty($pre_classes)) {
            $string .= ' class="'.implode(' ', $pre_classes).'"';
        }
        return empty($string)
            ? null
            : $string;
    }

    public static function stylesToString(
        string|array $styles
    ): ?string
    {
        if(is_string($styles)) $styles = preg_split('/(\s*;\s*)/', $styles, -1, PREG_SPLIT_NO_EMPTY);
        $string = '';
        $slugger = new AsciiSlugger();
        foreach ($styles as $key => $value) {
            if(!preg_match('/^\w[\w-]+\w$/', (string)$key)) {
                $splited = preg_split('/(\s*:\s*)/', $value, 2, PREG_SPLIT_NO_EMPTY);
                $key = $splited[0];
                $value = $splited[1];
            }
            $key = $slugger->slug(u($key)->snake());
            $string .= $key.': '.$value.'; ';
        }
        $string = trim($string);
        return empty($string)
            ? null
            : $string;
    }

}