<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
use Exception;
use UnitEnum;
use Stringable;
use Arr;

class Iterables extends BaseService
{

    public const TYPES_TYPEVALUES = [
        // 'auto',
        'string',
        'boolean',
        'integer',
        'double',
        'array',
        // 'object',
        'NULL',
    ];


    /*************************************************************************************
     * HTML Attribures
     *************************************************************************************/

    public static function toClassList(
        array|string $classes,
        bool $asString = false,
        string $pattern = '/(\s*[\r\n\s,]+\s*)/',
    ): array|string
    {
        if(is_array($classes)) {
            $classes = array_filter($classes, fn($item) => is_string($item) && strlen(trim($item)) > 0);
            $classes = implode(' ', $classes);
        }
        $final_classes = [];
        foreach (preg_split($pattern, trim($classes), -1, PREG_SPLIT_NO_EMPTY) as $class) {
            $class = trim($class);
            if(preg_match('/^[a-zA-Z-_][\w-]*$/', $class)) $final_classes[$class] = $class;
        }
        return $asString
            ? implode(' ', $final_classes)
            : $final_classes;
    }


    /*************************************************************************************
     * ARRAY / JSON
     *************************************************************************************/

    public static function TextToArray(string $text, string $pattern = '/(\s*[\r\n]+\s*)/'): array
    {
        $array = [];
        $index = 0;
        foreach(preg_split($pattern, trim($text), -1, PREG_SPLIT_NO_EMPTY) as $line) {
            if(preg_match('/[\w\d_-]+\s*=>\s*/', $line)) {
                $line = preg_split('/\s*=>\s*/', $line, 2);
                $array[trim($line[0])] = trim($line[1] ?? null);
            } else {
                $array[$index] = $line;
            }
            $index++;
        }
        return $array;
    }

    public static function ArrayToText(array $array): string
    {
        if(array_is_list($array)) {
            return implode(PHP_EOL, $array);
        } else {
            $list = [];
            foreach ($array as $key => $value) {
                $list[] = $key.' => '.$value;
            }
            return implode(PHP_EOL, $list);
        }
    }

    public static function isArrayIndex(mixed $index): bool
    {
        return is_int($index) || (is_string($index) && preg_match_all('/^\w+$/i', $index));
    }

    public static function EnumToArray(UnitEnum $enum): array
    {
        $array = [];
        foreach ($enum::cases() as $item) {
            $array[$item->name] = $item->value;
        }
        return $array;
    }

    public static function mergeArrays(
        array $array1,
        array $array2
    ): array
    {
        foreach ($array1 as $key => $value) {
            if(is_array($value)) {
                $array2[$key] = static::mergeArrays($value, $array2[$key] ?? []);
            } else {
                if(!isset($array2[$key])) $array2[$key] = $value;
            }
        }
        return $array2;
    }

    public static function removeEmptyElements(
        array $array,
    ): array
    {
        $array = array_map('trim', $array);
        return array_filter($array, function($item) { return !empty($item); });
    }

}