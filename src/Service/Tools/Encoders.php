<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Component\FinderLabo;
use Aequation\LaboBundle\Component\Overlay;
use Aequation\LaboBundle\Component\SplFileLabo;
use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Service\Tools\Times;
use Countable;
use DateTime;
use DateTimeInterface;
use SplFileInfo;
use Symfony\Component\String\ByteString;
use Twig\Markup;

class Encoders extends BaseService
{

    /*************************************************************************************
     * PASSWORD
     *************************************************************************************/

    public static function generatePassword(
        int $length = 12,
        string $chars = null
    ): string
    {
        return ByteString::fromRandom($length, $chars)->toString();
    }

    /*************************************************************************************
     * UID
     *************************************************************************************/

	/**
	 * 
	 * @see https://jasonmccreary.me/articles/php-convert-uniqid-to-timestamp/
	 * $timestamp = substr(uniqid(), 0, -5);
	 * echo date('r', hexdec($timestamp));  // Thu, 05 Sep 2013 15:55:04 -0400
	 */
	public static function geUniquid($prefix = "", string $separator = '.') {
		if(is_object($prefix)) $prefix = spl_object_hash($prefix).'_'.Times::getMicrotimeid().'@';
		if(!is_string($prefix)) $prefix = md5(json_encode($prefix)).'_'.Times::getMicrotimeid().'@';
        if(empty($prefix)) $prefix = 'UID';
		$uniquid = uniqid($prefix, true);
        if($separator !== '.') $uniquid = preg_replace('/\.+/', $separator, $uniquid);
        return $uniquid;
	}

        /**
     * Is EUID valid format
     * Ex. Aequation\LaboBundle\Model\User|65a8d53a34fc58.63711012
     * @param mixed $euid
     * @return boolean
     */
    public static function isEuidFormatValid(mixed $euid): bool
    {
        return is_string($euid) && preg_match('/^([a-zA-Z0-9\\\\]+)\\|([a-f0-9]{14}\\.\\d{8})$/', $euid);
    }

    /**
     * Get classname in EUID
     * @param string $euid
     * @return string|null
     */
    public static function getClassOfEuid(string $euid): ?string
    {
        return static::isEuidFormatValid($euid)
            ? preg_replace('/^([a-zA-Z0-9\\\\]+)\\|([a-f0-9]{14}\\.\\d{8})$/', '$1', $euid)
            : null;
        
    }


    /*************************************************************************************
     * VARIABLES
     *************************************************************************************/

    /**
     * Swap two variables
     * @param mixed $x
     * @param mixed $y
     * @return void
     */
    public static function swap(&$x, &$y) {
        extract(array('x' => $y, 'y' => $x));
    }
    

    /*************************************************************************************
     * RANDOMS
     *************************************************************************************/

    /**
     * Generate Random String of numbers
     *
     * @param integer $length
     * @return string
     */
    public static function generateRandomNumber(
        int $length
    ): string
    {
        $max = (10 ** $length) - 1;
        return str_pad((string)rand(0, $max), $length, '0', STR_PAD_LEFT);
    } 


    /*************************************************************************************
     * JSON
     *************************************************************************************/

    /**
     * Is a valid Json
     * Target Version: PHP 8.3
     * @param mixed $json
     * @return boolean
     */
    public static function isJson(mixed $json): bool
    {
        return is_string($json)
            ? json_validate($json)
            : false;
        // json_decode($json);
        // return json_last_error() === JSON_ERROR_NONE;
    }

    public static function fromJson(mixed $json): mixed
    {
        return is_string($json) && json_validate($json)
            ? json_decode($json)
            : $json;
    }


    /*************************************************************************************
     * DUMPS
     *************************************************************************************/

    public static function getPrintr(
		mixed $value,
		int $max_level = 3,
		bool $printType = false,
		int $current_level = 0
	): Markup
    {
        $current_level++;
        $type = gettype($value);
        $children_count = is_countable($value) ? count($value) : 0;
        $printr = $current_level > 0 ? '' : '<div>';
        $shortname = is_object($value) ? ' '.Classes::getShortname($value) : null;
        $title = is_object($value) ? get_class($value) : null;
        $basetype = '<small class="text-muted"'.($title ? ' title="'.$title.'"' : '').'>[<i>'.$type.'</i><strong>'.$shortname.'</strong>'.(is_countable($value) ? ' ('.$children_count.')' : '').']</small> ';
        $typed = $printType ? $basetype.' ' : '';
        switch ($type) {
            case 'array':
                if($current_level > $max_level) {
                    $printr .= $basetype.$children_count.' items...';
                } else {
                    $printr .= ($typed ? '<div>'.$typed.'</div>' : '').($children_count > 0 ? '<div><ul class="mb-0">' : '');
                    $is_list = array_is_list($value);
                    foreach ($value as $key => $val) {
                        $printr .= '<li>'.($is_list ? '' : $key.' => ').static::getPrintr($val, $max_level, $printType, $current_level).'</li>';
                    }
                    $printr .= $children_count > 0 ? '</ul></div>' : '';
                }
                break;
            case 'boolean':
                $printr .= $typed.'<span class="text-info">'.($value ? 'true' : 'false').'</span>';
                break;
            case 'object':
                switch (true) {
                    case $value instanceof DateTimeInterface:
                        // $printr .= $typed.'<span class="text-info">'.$value->format(DATE_ATOM).'</span>';
                        break;
                    // case $value instanceof SplFileInfo:
                    case $value instanceof Overlay:
                        $printr .= static::getPrintr($value->toArray(), $max_level, $printType, $current_level);
                        break;
                    case $value instanceof SplFileLabo:
                        $printr .= static::getPrintFiles($value, $max_level, $printType, $current_level - 1);
                        break;
                    case $value instanceof FinderLabo:
                        if($current_level > $max_level) {
                            $printr .= $basetype.$children_count.' files...';
                        } else {
                            $printr .= '<div>'.$typed.'<span class="text-info">'.Classes::getShortname($value).'</span></div>'.($children_count > 0 ? '<div><ul class="mb-0">' : '');
                            foreach ($value->getChildrenArray() as $key => $val) {
                                $printr .= '<li>'.$key.' => '.static::getPrintr($val, $max_level, $printType, $current_level).'</li>';
                            }
                            $printr .= $children_count > 0 ? '</ul></div>' : '';
                        }
                        break;
                    default:
                        $printr .= $typed.'<span class="text-info">'.get_class($value).'</span>';
                        break;
                }
                // if($value instanceof DateTimeInterface) $type = 'DateTimeInterface';
                $value = $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : get_class($value);
                $printr .= $typed.'<span class="text-info">'.$value.'</span>';
                break;
            case 'NULL':
                $printr .= $typed.'<span class="text-info">'.json_encode($value).'</span>';
                break;
            case 'unknown type':
                $printr .= $typed.'<span class="text-info">'.'unknown type'.'</span>';
                break;
            case 'resource':
                $printr .= $typed.'<span class="text-info">'.'resource'.'</span>';
                break;
            default:
                $printr .= $typed.$value;
                break;
        }
        $printr .= $current_level > 0 ? '' : '</div>';
        $current_level--;
        return Strings::markup($printr);
    }

    public static function getPrintFiles(
		SplFileLabo $value,
		int $max_level = 3,
		bool $printType = false,
		int $current_level = 0
	): Markup
    {
        $current_level++;
        $type = gettype($value);
        $children_count = is_countable($value) ? count($value) : 0;
        $printr = $current_level > 0 ? '' : '<div>';
        $shortname = is_object($value) ? ' '.Classes::getShortname($value) : null;
        $title = is_object($value) ? get_class($value) : null;
        $basetype = '<small class="text-muted cursor-help"'.($title ? ' title="'.$title.'"' : '').'>[<i>'.$type.'</i><strong>'.$shortname.'</strong>'.(is_countable($value) ? ' ('.$children_count.')' : '').']</small> ';
        $typed = $printType ? $basetype : '';
        if($value->isDir()) {
            $printr .= '<div><span class="text-info cursor-help" title="'.$value->getPathname().'"><i class="fa fa-folder fa-fw"></i> '.$value->getFilename().'</span>'.($typed ? ' '.$typed : '').($current_level > $max_level ? '...' : '').'</div>';
            if($current_level <= $max_level && $children_count > 0) {
                $printr .= '<div><ul class="mb-0">';
                foreach ($value->sortByType()->getChildrenArray() as $val) {
                    $printr .= '<li style="list-style-type: none;">'.static::getPrintFiles($val, $max_level, $printType, $current_level).'</li>';
                }
                $printr .='</ul></div>';
            }
        } else {
            $printr .= '<span class="text-info cursor-help" title="'.$value->getPathname().'"><i class="fa fa-file fa-fw text-secondary"></i> '.$value->getFilename().'</span> '.$typed;
        }
        $printr .= $current_level > 0 ? '' : '</div>';
        $current_level--;
        return Strings::markup($printr);
    }


    /*************************************************************************************
     * TRANS TYPES
     *************************************************************************************/

    /**
     * Set value to type
     * @see https://www.php.net/manual/fr/function.settype.php
     * 
     * @param string $paramvalue
     * @param string $typevalue
     * @return mixed
     */
    public static function stringToType(
        string $paramvalue,
        string $typevalue
    ): mixed
    {
        $paramvalue = static::fromJson($paramvalue);
        switch ($typevalue) {
            case 'NULL':
                $paramvalue = null;
                break;
            case 'integer':
                $paramvalue = is_scalar($paramvalue)
                    ? intval($paramvalue)
                    : 0;
                break;
            case 'double':
                if(is_string($paramvalue)) $paramvalue = preg_replace('/,+/', '.', $paramvalue);
                $paramvalue = is_scalar($paramvalue)
                    ? floatval($paramvalue)
                    : 0.0;
                break;
            case 'boolean':
                $paramvalue = !in_array(strtolower($paramvalue), ['0', 'false', 'non', '', 'null', 'faux', 'no', 'n']);
                break;
            case 'array':
                if(is_string($paramvalue)) $paramvalue = explode(PHP_EOL, $paramvalue);
                break;
            default: // string
                settype($paramvalue, 'string');
                break;
        }
        return $paramvalue;
    }

    public static function fieldRepresentation(
        mixed $value,
        // ?int $maxlength = null,
        // bool $cut_word = true,
        bool $asHtml = true,
    ): string|Markup
    {
        // switch (gettype($value)) {
        //     case 'object':
                
        //         break;
        //     default:
        //         # code...
        //         break;
        // }
        $value = static::getPrintr($value, 0, false);
        // if(!empty($maxlength) && (is_string($value) || $value instanceof Markup)) $value = Strings::cutAt($value, $maxlength, $cut_word);
        return $asHtml
            ? Strings::markup($value)
            : $value;
    }


}