<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;

class Mesures extends BaseService
{

    /** ***********************************************************************************
     * NUMBERS
     *************************************************************************************/

    /**
     * Swap values
     * @param mixed &$a
     * @param mixed &$b
     * @return void
     */
    public static function swapValues(
        mixed &$a,
        mixed &$b,
    ): void
    {
        list($a, $b) = array($b, $a);
    }

    /**
     * Swap values IF min/max
     * Returns true in swaped / false if not
     * @param mixed &$min
     * @param mixed &$max
     * @return boolean
     */
    public static function sortMinMax(
        mixed &$min,
        mixed &$max,
    ): bool
    {
        if(floatval($min) <= floatval($max)) return false;
        static::swapValues($min, $max);
        return true;
    }

    /** ***********************************************************************************
     * Units
     *************************************************************************************/

    public static function fileSizeText(int $octets): string
    {
        switch (true) {
            case $octets >= (1024 * 1024 * 1024):
                return number_format($octets / (1024 * 1024 * 1024), 2, ',', '').'Go';
                break;
            case $octets >= (1024 * 1024):
                return number_format($octets / (1024 * 1024), 2, ',', '').'Mo';
                break;
            case $octets >= 1024:
                return intval($octets / 1024).'Ko';
                break;
            default:
                return $octets.'o';
                break;
        }
    }

}