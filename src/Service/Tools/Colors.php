<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
use Exception;

class Colors extends BaseService
{

    /** ***********************************************************************************
     * COLORS
     *************************************************************************************/

	public static function getContrastMono($hexColor, $contrast = 15, $resultLight = '#FFFFFF', $resultDark = '#000000') {
		// https://stackoverflow.com/questions/1331591/given-a-background-color-black-or-white-text
		if(!preg_match('/^#([0-9A-F]{6}|[0-9A-F]{3})/i', $hexColor)) {
			/**
			 * Convert to hexa
			 * @see https://convertingcolors.com/blog/article/convert_rgb_to_hex_with_php.html
			 */
			static::RgbToHexa($hexColor);
			if(!preg_match('/^#([0-9A-F]{6}|[0-9A-F]{3})/i', $hexColor)) throw new Exception(sprintf('Error line %d %s(): hexa color code needed. Got %s!', __LINE__, __METHOD__, $hexColor));
		}
		// hexColor RGB
		$R1 = hexdec(substr($hexColor, 1, 2));
		$G1 = hexdec(substr($hexColor, 3, 2));
		$B1 = hexdec(substr($hexColor, 5, 2));
		// Black RGB
		$blackColor = "#000000";
		$R2BlackColor = hexdec(substr($blackColor, 1, 2));
		$G2BlackColor = hexdec(substr($blackColor, 3, 2));
		$B2BlackColor = hexdec(substr($blackColor, 5, 2));
		// Calc contrast ratio
		$L1 = 0.2126 * pow($R1 / 255, 2.2) + 0.7152 * pow($G1 / 255, 2.2) + 0.0722 * pow($B1 / 255, 2.2);
		$L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) + 0.7152 * pow($G2BlackColor / 255, 2.2) + 0.0722 * pow($B2BlackColor / 255, 2.2);
		$contrastRatio = 0;
		if ($L1 > $L2) {
			$contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
		} else {
			$contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
		}
		// If contrast is greater than $contrast, return dark color
		// if not, return light color.
		return $contrastRatio > $contrast ? $resultDark : $resultLight;
	}

	public static function getColorInverse($hexColor) {
		/**
		 * @see https://www.jonasjohn.de/snippets/php/color-inverse.htm
		 * Voir aussi : Lighten or darken a given colour
		 * @see https://gist.github.com/stephenharris/5532899
		 */
		$hexColor = str_replace('#', '', $hexColor);
		if (strlen($hexColor) != 6) { return '#000000'; }
		$rgb = '';
		for ($x=0; $x<3; $x++){
			$c = 255 - hexdec(substr($hexColor,(2*$x),2));
			$c = ($c < 0) ? 0 : dechex($c);
			$rgb .= (strlen($c) < 2) ? '0'.$c : $c;
		}
		return '#'.$rgb;
	}

	public static function RgbToHexa(&$rgb) {
		if(preg_match('/^#([0-9A-F]{6}|[0-9A-F]{3})/i', $rgb)) return $rgb;
		// $sRegex = '/rgba?(\s?([0-9]{1,3}),\s?([0-9]{1,3}),\s?([0-9]{1,3})/i';
		$sRegex = '/^rgba?\(\s?([0-9]{1,3}),\s?([0-9]{1,3}),\s?([0-9]{1,3})/i';
		preg_match($sRegex, $rgb, $matches);
		array_walk($matches, function(&$value, $key) {
			$value = ['decimal' => $value, 'hexa' => str_pad(dechex((int) $value), 2, '0', STR_PAD_LEFT)];
		});
		return $rgb = '#'.dechex($matches[1]['hexa']).dechex($matches[2]['hexa']).dechex($matches[3]['hexa']);

	}

}