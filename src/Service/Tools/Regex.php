<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
use Symfony\Component\Finder\Glob;

class Regex extends BaseService
{

    /**
     * Checks whether the string is a regex.
     */
    public static function isRegex(
        string $str
    ): bool
    {
        $availableModifiers = 'imsxuADUn';
        if (preg_match('/^(.{3,}?)['.$availableModifiers.']*$/', $str, $m)) {
            $start = substr($m[1], 0, 1);
            $end = substr($m[1], -1);
            if ($start === $end) {
                return !preg_match('/[*?[:alnum:] \\\\]/', $start);
            }
            foreach ([['{', '}'], ['(', ')'], ['[', ']'], ['<', '>']] as $delimiters) {
                if ($start === $delimiters[0] && $end === $delimiters[1]) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Converts glob to regexp.
     * PCRE patterns are left unchanged.
     * Glob strings are transformed with Glob::toRegex().
     * @param string $str Pattern: glob or regexp
     */
    public static function toRegex(
        string $str
    ): string
    {
        return static::isRegex($str) ? $str : Glob::toRegex($str);
    }

}