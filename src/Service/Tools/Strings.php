<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Model\Attribute\CssClasses;
use Aequation\LaboBundle\Service\Base\BaseService;
// Symfony
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\FrenchInflector;
use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\u;
// use Faker\Factory as Faker;
use DateTime;
use DOMDocument;
use DOMDocumentType;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMText;
use Exception;
use Stringable;
use Twig\Markup;

class Strings extends BaseService
{

	public const CHARSET = 'UTF-8';

	public static function markup(
		?string $html,
		?string $charset = null
	): Markup
	{
		return new Markup(
			content: $html ?? '',
			charset: $charset ?? static::CHARSET
		);
	}

    /** ***********************************************************************************
     * STRING / TEXTS
     * @see https://symfony.com/doc/current/components/string.html
     *************************************************************************************/

    public static function stringFormated(
        string $string,
        string $type
    ): string
    {
        switch ($type) {
            case 'camel':
                return u($string)->camel();
                break;
            case 'pascal':
                return u($string)->camel()->title();
                break;
            case 'folded':
                return u($string)->folded();
                break;
            case 'snake':
                return u($string)->snake();
                break;
            case 'lower':
                return u($string)->lower();
                break;
            case 'upper':
                return u($string)->upper();
                break;
            default:
                return $string;
                break;
        }
    }

	public static function cutAt(string $string, int $length, bool $cut_word = false): string
	{
		$break_point = "_____@@#@@_!!!_@@#@@_____";
		$new_string = wordwrap($string, $length, $break_point, $cut_word);
		$new_string = explode($break_point, $new_string);
		$new_string = reset($new_string);
		if(strlen($new_string) < strlen($string)) $new_string .= '...';
		return $new_string;
	}

    public static function getBefore(
        string $string,
        string $needle,
        bool $includeNeedle = false,
		int $offset = 0,
    ): string
    {
        return u($string)->before(needle: $needle, includeNeedle: $includeNeedle, offset: $offset);
    }

    public static function getBeforeLast(
        string $string,
        string $needle,
        bool $includeNeedle = false,
		int $offset = 0,
    ): string
    {
        return u($string)->beforeLast(needle: $needle, includeNeedle: $includeNeedle, offset: $offset);
    }

    public static function getAfter(
        string $string,
        string $needle,
        bool $includeNeedle = false,
		int $offset = 0,
    ): string
    {
        return u($string)->after(needle: $needle, includeNeedle: $includeNeedle, offset: $offset);
    }

    public static function getAfterLast(
        string $string,
        string $needle,
        bool $includeNeedle = false,
		int $offset = 0,
    ): string
    {
        return u($string)->afterLast(needle: $needle, includeNeedle: $includeNeedle, offset: $offset);
    }

    public static function pluralize(
        string $string,
        string $language = 'en',
    ): string
    {
        switch (substr($language, 0, 2)) {
            case 'fr':
                $inflector = new FrenchInflector();
                $plur = $inflector->pluralize($string)[0];
                break;
            default: // en and others...
                $inflector = new EnglishInflector();
                $plur = $inflector->pluralize($string)[0];
                break;
        }
        $plur = preg_replace('/[sx]+(x|s)$/i', '$1', $plur);
        return $plur;
    }

    public static function singularize(
        string $string,
        string $language = 'en',
    ): string
    {
        switch (substr($language, 0, 2)) {
            case 'fr':
                $inflector = new FrenchInflector();
                return $inflector->singularize($string)[0];
                break;
            default: // en and others...
                $inflector = new EnglishInflector();
                return $inflector->singularize($string)[0];
                break;
        }
    }

    public static function getSlug(string $string, string $separator = '-', bool $toLower = false, ?string $locale = null): string
    {
        $slugger = new AsciiSlugger();
        $slug = $slugger->slug(string: $string, separator: $separator, locale: $locale)->__toString();
		return $toLower
			? strtolower($slug)
			: $slug;
    }

    // public static function getFakeText(
    //     int $paragraphs = 3,
    //     int $paraph_length = 300,
    //     string $locale = 'fr_FR',
    // ): string
    // {
    //     $faker = Faker::create($locale);
    //     $text = '';
    //     foreach (range(1, $paragraphs) as $n) {
    //         $text .= '<p>'.$faker->realText($faker->numberBetween(intval($paraph_length - ($paraph_length / 3)), $paraph_length)).'</p>';
    //     }
    //     return $text;
    //     // return '<div>'.$text.'</div>';
    // }

	public static function explode(
		string $string,
		string $separator = ' ',
		int $limit = PHP_INT_MAX,
		bool $remove_duplicates = false,
		bool $remove_empty = true,
	): array
	{
		$array = $remove_duplicates
			? array_unique(explode($separator, $string, $limit))
			: explode($separator, $string, $limit);
		$array = array_map('trim', $array);
		return $remove_empty
			? Iterables::removeEmptyElements($array)
			: $array;
	}

    public static function toClassList(
        array|string $classes
    ): string
	{
		return Iterables::toClassList($classes, true);
	}

	public static function text2array(
		string $text,
		string $spliter = '#\s*(?:\r\n|\n|\r)+\s*#u'
	): array
	{
		if(!static::hasText($text)) return [];
		$array = preg_split($spliter, $text, -1, PREG_SPLIT_NO_EMPTY);
		return array_filter($array, function($value) {
			return static::hasText($value);
		});
	}

    #[CssClasses(target: 'value')]
    public static function getCssClasses(): array
    {
        $css = [
			'pre' => "text-sky-700 text-lg font-semibold",
			'blockquote' => "text-amber-700 text-lg font-semibold",
			'u' => "underline",
			'i' => "italic",
			'em' => "italic",
			'del' => "underline",
			'ul' => "list-disc pl-8",
			'li' => "",
			'a' => "inline-block rounded-md bg-sky-600 hover:bg-sky-400 text-white my-2 px-4 py-2 !no-underline"
		];
		return $css;
    }

	public static function formateForWebpage(?string $text, int $mode = 1): Markup
	{
		$text = (string) $text;
		switch ($mode) {
			case 0:
				// Raw HTML
				$code = $text;
				break;
			case 1:
				// CKEditorField
				$code = preg_replace_callback('#(&lt;)(\/?twig:)(.*?)(&gt;)#', fn ($matches) => '<'.$matches[2].htmlspecialchars_decode($matches[3]).'>', $text);
				break;
			default:
				// TextEditorField
				$css = static::getCssClasses();
				$replaces = [
					'/<h1>/' => '<h3>',
					'/<\/h1>/' => '</h3>',
					'/<pre>/' => '<div class="'.$css['pre'].'">',
					'/<\/pre>/' => '</div>',
					'/<blockquote>/' => '<div class="'.$css['blockquote'].'">',
					'/<\/blockquote>/' => '</div>',
					'/<u>/' => '<div class="'.$css['u'].'">',
					'/<\/u>/' => '</div>',
					'/<i>/' => '<span class="'.$css['i'].'">',
					'/<\/i>/' => '</span>',
					'/<em>/' => '<span class="'.$css['em'].'">',
					'/<\/em>/' => '</span>',
					'/<del>/' => '<span class="'.$css['del'].'">',
					'/<\/del>/' => '</span>',
					'/<ul>/' => '<ul class="'.$css['ul'].'">',
					'/<\/ul>/' => '</ul>',
					'/<li>/' => '<li class="'.$css['li'].'"><div>',
					'/<\/li>/' => '</div></li>',
					// Twig components
					'/&lt;twig:(.*?)&gt;/' => '<twig:$1>',
					'/&lt;\/twig:(.*?)&gt;/' => '</twig:$1>',
				];
				$code = preg_replace(array_keys($replaces), $replaces, $text);
				break;
			}
			return static::markup($code);
	}

	public static function normalizeTelephoneNumber(string $phone): string
	{
		$phone = preg_replace('/\D+/', '', $phone);
		// Add spaces every 2 digits for readability
		$phone = trim(implode(' ', str_split($phone, 2)));
		return $phone;
	}

    /** ***********************************************************************************
     * HTML TEXTS
     *************************************************************************************/

	public static function textOrNull(
		mixed $element,
		bool $striptags = false,
		mixed $nullValue = null,
	): mixed
	{
		if(is_object($element)) {
			$element = $element instanceof Stringable
				? $element->__toString()
				: null;
		}
		$element = (string) $element;
		return is_string($element) && strlen(trim(strip_tags($element)))
			? trim($striptags ? strip_tags($element) : $element)
			: $nullValue;
	}

	public static function hasText(
		mixed $element
	): bool
	{
		if(is_object($element)) {
			$element = $element instanceof Stringable
				? $element->__toString()
				: null;
		}
		$element = (string) $element;
		return is_string($element)
			? strlen(trim(strip_tags($element))) > 0
			: false;
	}

	public static function htmlAttributes(
		array $attributes,
	): string
	{
        $attrs = '';
        foreach ($attributes as $attribute => $value) {
			if($attribute === "class" && is_string($value)) $value = preg_split('/\\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
            if(is_array($value)) {
                $separ = $attribute === 'style' ? '; ' : ' ';
				$value = Iterables::removeEmptyElements(array_unique($value));
                $value = implode($separ, $value);
            };
            $attrs .= ' '.$attribute.'="'.trim($value).'"';
        }
		return $attrs;
	}

    public static function decorate(
        string $text,
        string $tagname,
        array $attributes = [],
    ): Markup
    {
        return static::markup('<'.$tagname.static::htmlAttributes($attributes).'>'.$text.'</'.$tagname.'>');
    }

	public static function makeHtmlList(
		array $texts,
		string $tagname = 'ul',
		array $attributes = [],
		string $items_tag = 'li',
	): Markup
	{
		$html = '';
		if(empty($texts)) return static::markup($html);
		foreach ($texts as $text) {
			$html .= static::decorate($text, $items_tag);
		}
		return static::decorate($html, $tagname, $attributes);
	}




    /**
     * @return array<string, bool | string>
     */
	public static function defaultOptions(): array {
		return [
			'ignore_errors' => false,
			'drop_links'    => false,
			'char_set'      => 'auto'
		];
	}

	/**
	 * @see https://github.com/soundasleep/html2text/blob/master/src/Html2Text.php
	 * Tries to convert the given HTML into a plain text format - best suited for
	 * e-mail display, etc.
	 *
	 * <p>In particular, it tries to maintain the following features:
	 * <ul>
	 *   <li>Links are maintained, with the 'href' copied over
	 *   <li>Information in the &lt;head&gt; is lost
	 * </ul>
	 *
	 * @param string $html the input HTML
	 * @param boolean|array<string, bool | string> $options if boolean, Ignore xml parsing errors, else ['ignore_errors' => false, 'drop_links' => false, 'char_set' => 'auto']
	 * @return string the HTML converted, as best as possible, to text
	 * @throws Exception if the HTML could not be loaded as a {@link DOMDocument}
	 */
	public static function convert(string $html, $options = []): string {
		if ($options === false || $options === true) {
			// Using old style (< 1.0) of passing in options
			$options = ['ignore_errors' => $options];
		}
		$options = array_merge(static::defaultOptions(), $options);
		// check all options are valid
		foreach ($options as $key => $value) {
			if (!in_array($key, array_keys(static::defaultOptions()))) {
				throw new \InvalidArgumentException("Unknown html2text option '$key'. Valid options are " . implode(',', static::defaultOptions()));
			}
		}
		$is_office_document = self::isOfficeDocument($html);
		if ($is_office_document) {
			// remove office namespace
			$html = str_replace(["<o:p>", "</o:p>"], "", $html);
		}
		$html = self::fixNewlines($html);
		// use mb_convert_encoding for legacy versions of php
		if (PHP_MAJOR_VERSION * 10 + PHP_MINOR_VERSION < 81 && mb_detect_encoding($html, "UTF-8", true)) {
			$html = mb_convert_encoding($html, "HTML-ENTITIES", "UTF-8");
		}
		$doc = self::getDocument($html, $options);
		$output = self::iterateOverNode($doc, null, false, $is_office_document, $options);
		// process output for whitespace/newlines
		$output = self::processWhitespaceNewlines($output);
		return $output;
	}

	/**
	 * Unify newlines; in particular, \r\n becomes \n, and
	 * then \r becomes \n. This means that all newlines (Unix, Windows, Mac)
	 * all become \ns.
	 * @param string $text text with any number of \r, \r\n and \n combinations
	 * @return string the fixed text
	 */
	public static function fixNewlines(string $text): string {
		// replace \r\n to \n
		$text = str_replace("\r\n", "\n", $text);
		// remove \rs
		$text = str_replace("\r", "\n", $text);
		return $text;
	}

	/**
     * @return array<string>
     */
	public static function nbspCodes(): array {
		return [
			"\xc2\xa0",
			"\u00a0",
		];
	}

	/**
     * @return array<string>
     */
	public static function zwnjCodes(): array {
		return [
			"\xe2\x80\x8c",
			"\u200c",
		];
	}

	/**
	 * Remove leading or trailing spaces and excess empty lines from provided multiline text
	 * @param string $text multiline text any number of leading or trailing spaces or excess lines
	 * @return string the fixed text
	 */
	public static function processWhitespaceNewlines(string $text): string {
		// remove excess spaces around tabs
		$text = preg_replace("/ *\t */im", "\t", $text);
		// remove leading whitespace
		$text = ltrim($text);
		// remove leading spaces on each line
		$text = preg_replace("/\n[ \t]*/im", "\n", $text);
		// convert non-breaking spaces to regular spaces to prevent output issues,
		// do it here so they do NOT get removed with other leading spaces, as they
		// are sometimes used for indentation
		$text = self::renderText($text);
		// remove trailing whitespace
		$text = rtrim($text);
		// remove trailing spaces on each line
		$text = preg_replace("/[ \t]*\n/im", "\n", $text);
		// unarmor pre blocks
		$text = self::fixNewLines($text);
		// remove unnecessary empty lines
		$text = preg_replace("/\n\n\n*/im", "\n\n", $text);
		return $text;
	}

	/**
     * Can we guess that this HTML is generated by Microsoft Office?
     * @param string $html
     * @return boolean
     */
	public static function isOfficeDocument(string $html): bool {
		return strpos($html, "urn:schemas-microsoft-com:office") !== false;
	}

    /**
     * Is text only composed of invisible chars (spaces, tabs, etc.)
     * @param string $text
     * @return boolean
     */
	public static function isWhitespace(string $text): bool {
		return strlen(trim(self::renderText($text), "\n\r\t ")) === 0;
	}

	/**
	 * Parse HTML into a DOMDocument
	 * @param string $html the input HTML
	 * @param array<string, bool | string> $options
	 * @return DOMDocument the parsed document tree
	 */
	private static function getDocument(string $html, array $options): DOMDocument {
		$doc = new DOMDocument();
		$html = trim($html);
		if (!$html) {
			// DOMDocument doesn't support empty value and throws an error
			// Return empty document instead
			return $doc;
		}
		if ($html[0] !== '<') {
			// If HTML does not begin with a tag, we put a body tag around it.
			// If we do not do this, PHP will insert a paragraph tag around
			// the first block of text for some reason which can mess up
			// the newlines. See pre.html test for an example.
			$html = '<body>' . $html . '</body>';
		}
		$header = '';
		// use char sets for modern versions of php
		if (PHP_MAJOR_VERSION * 10 + PHP_MINOR_VERSION >= 81) {
			// use specified char_set, or auto detect if not set
			$char_set = ! empty($options['char_set']) ? $options['char_set'] : 'auto';
			if ('auto' === $char_set) {
				$char_set = mb_detect_encoding($html);
			} else if (strpos($char_set, ',')) {
				mb_detect_order($char_set);
				$char_set = mb_detect_encoding($html);
			}
			// turn off error detection for Windows-1252 legacy html
			if (strpos($char_set, '1252')) {
				$options['ignore_errors'] = true;
			}
			$header = '<?xml version="1.0" encoding="' . $char_set . '">';
		}
		if (! empty($options['ignore_errors'])) {
			$doc->strictErrorChecking = false;
			$doc->recover = true;
			$doc->xmlStandalone = true;
			$old_internal_errors = libxml_use_internal_errors(true);
			$load_result = $doc->loadHTML($header . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET | LIBXML_PARSEHUGE);
			libxml_use_internal_errors($old_internal_errors);
		}
		else {
			$load_result = $doc->loadHTML($header . $html);
		}
		if (!$load_result) {
			throw new Exception("Could not load HTML - badly formed?", $html);
		}
		return $doc;
	}

	/**
	 * Replace any special characters with simple text versions, to prevent output issues:
	 * - Convert non-breaking spaces to regular spaces; and
	 * - Convert zero-width non-joiners to '' (nothing).
	 * This is to match our goal of rendering documents as they would be rendered
	 * by a browser.
     * @param string $text
     * @return string
     */
	private static function renderText(string $text): string {
		$text = str_replace(self::nbspCodes(), " ", $text);
		$text = str_replace(self::zwnjCodes(), "", $text);
		return $text;
	}

    /**
     * Get next child name
     * @param DOMNode|null $node
     * @return string|null
     */
	private static function nextChildName(?DOMNode $node): ?string {
		// get the next child
		$nextNode = $node->nextSibling;
		while ($nextNode != null) {
			if ($nextNode instanceof DOMText) {
				if (!self::isWhitespace($nextNode->wholeText)) {
					break;
				}
			}
			if ($nextNode instanceof DOMElement) {
				break;
			}
			$nextNode = $nextNode->nextSibling;
		}
		$nextName = null;
		if (($nextNode instanceof DOMElement || $nextNode instanceof DOMText) && $nextNode != null) {
			$nextName = strtolower($nextNode->nodeName);
		}
		return $nextName;
	}

	/**
     * @param array<string, bool | string> $options
     */
	private static function iterateOverNode(DOMNode $node, ?string $prevName, bool $in_pre, bool $is_office_document, array $options): string {
		if ($node instanceof DOMText) {
		  // Replace whitespace characters with a space (equivilant to \s)
			if ($in_pre) {
				$text = "\n" . trim(self::renderText($node->wholeText), "\n\r\t ") . "\n";
				// Remove trailing whitespace only
				$text = preg_replace("/[ \t]*\n/im", "\n", $text);
				// armor newlines with \r.
				return str_replace("\n", "\r", $text);
			}
			$text = self::renderText($node->wholeText);
			$text = preg_replace("/[\\t\\n\\f\\r ]+/im", " ", $text);
			if (!self::isWhitespace($text) && ($prevName == 'p' || $prevName == 'div')) {
				return "\n" . $text;
			}
			return $text;
		}
		if ($node instanceof DOMDocumentType || $node instanceof DOMProcessingInstruction) {
			// ignore
			return "";
		}
		/** @var DOMElement */
		$nodeElem = $node;
		$name = strtolower($nodeElem->nodeName);
		$nextName = self::nextChildName($nodeElem);
		// if($name !== "#document") dd($nodeElem);
		// start whitespace
		switch ($name) {
			case "hr":
				$prefix = '';
				if ($prevName != null) {
					$prefix = "\n";
				}
				return $prefix . "---------------------------------------------------------------\n";
			case "style":
			case "head":
			case "title":
			case "meta":
			case "script":
				// ignore these tags
				return "";
			case "h1":
			case "h2":
			case "h3":
			case "h4":
			case "h5":
			case "h6":
			case "ol":
			case "ul":
			case "pre":
				// add two newlines
				$output = "\n\n";
				break;
			case "td":
			case "th":
				// add tab char to separate table fields
				$output = "\t";
				break;
			case "p":
				// Microsoft exchange emails often include HTML which, when passed through
				// html2text, results in lots of double line returns everywhere.
				//
				// To fix this, for any p element with a className of `MsoNormal` (the standard
				// classname in any Microsoft export or outlook for a paragraph that behaves
				// like a line return) we skip the first line returns and set the name to br.
				// @phpstan-ignore-next-line
				if ($is_office_document && $nodeElem->getAttribute('class') == 'MsoNormal') {
					$output = "";
					$name = 'br';
					break;
				}
				// add two lines
				$output = "\n\n";
				break;
			case "tr":
				// add one line
				$output = "\n";
				break;
			case "div":
				$output = "";
				if ($prevName !== null) {
					// add one line
					$output .= "\n";
				}
				break;
			case "li":
				$output = "- ";
				break;
			default:
				// print out contents of unknown tags
				$output = "";
				break;
		}
		// debug
		//$output .= "[$name,$nextName]";
		if (isset($nodeElem->childNodes)) {
			$n = $nodeElem->childNodes->item(0);
			$previousSiblingNames = [];
			$previousSiblingName = null;
			$parts = [];
			$trailing_whitespace = 0;
			while ($n != null) {
				$text = self::iterateOverNode($n, $previousSiblingName, $in_pre || $name == 'pre', $is_office_document, $options);
				// Pass current node name to next child, as previousSibling does not appear to get populated
				if ($n instanceof DOMDocumentType
					|| $n instanceof DOMProcessingInstruction
					|| ($n instanceof DOMText && self::isWhitespace($text))) {
					// Keep current previousSiblingName, these are invisible
					$trailing_whitespace++;
				}
				else {
					$previousSiblingName = strtolower($n->nodeName);
					$previousSiblingNames[] = $previousSiblingName;
					$trailing_whitespace = 0;
				}
				$nodeElem->removeChild($n);
				$n = $nodeElem->childNodes->item(0);
				$parts[] = $text;
			}
			// Remove trailing whitespace, important for the br check below
			while ($trailing_whitespace-- > 0) {
				array_pop($parts);
			}
			// suppress last br tag inside a node list if follows text
			$last_name = array_pop($previousSiblingNames);
			if ($last_name === 'br') {
				$last_name = array_pop($previousSiblingNames);
				if ($last_name === '#text') {
					array_pop($parts);
				}
			}
			$output .= implode('', $parts);
		}
		// end whitespace
		switch ($name) {
			case "h1":
			case "h2":
			case "h3":
			case "h4":
			case "h5":
			case "h6":
			case "pre":
			case "p":
				// add two lines
				$output .= "\n\n";
				break;
			case "br":
				// add one line
				$output .= "\n";
				break;
			case "div":
				break;
			case "a":
				// links are returned in [text](link) format
				// @phpstan-ignore-next-line
				$href = $nodeElem->getAttribute("href");
				$output = trim($output);
				// remove double [[ ]] s from linking images
				if (substr($output, 0, 1) == "[" && substr($output, -1) == "]") {
					$output = substr($output, 1, strlen($output) - 2);
					// for linking images, the title of the <a> overrides the title of the <img>
					// @phpstan-ignore-next-line
					if ($nodeElem->getAttribute("title")) {
						// @phpstan-ignore-next-line
						$output = $nodeElem->getAttribute("title");
					}
				}
				// if there is no link text, but a title attr
				// @phpstan-ignore-next-line
				if (!$output && $nodeElem->getAttribute("title")) {
					// @phpstan-ignore-next-line
					$output = $nodeElem->getAttribute("title");
				}
				if ($href == null) {
					// it doesn't link anywhere
					// @phpstan-ignore-next-line
					if ($nodeElem->getAttribute("name") != null) {
						if ($options['drop_links']) {
							$output = "$output";
						} else {
							$output = "[$output]";
						}
					}
				} else {
					if ($href == $output || $href == "mailto:$output" || $href == "http://$output" || $href == "https://$output") {
						// link to the same address: just use link
						$output = "$output";
					} else {
						// replace it
						if ($output) {
							if ($options['drop_links']) {
								$output = "$output";
							} else {
								$output = "[$output]($href)";
							}
						} else {
							// empty string
							$output = "$href";
						}
					}
				}
				// does the next node require additional whitespace?
				switch ($nextName) {
					case "h1": case "h2": case "h3": case "h4": case "h5": case "h6":
						$output .= "\n";
						break;
				}
				break;
			case "img":
				// @phpstan-ignore-next-line
				if ($nodeElem->getAttribute("title")) {
					// @phpstan-ignore-next-line
					$output = "[" . $nodeElem->getAttribute("title") . "]";
				// @phpstan-ignore-next-line
				} elseif ($nodeElem->getAttribute("alt")) {
					// @phpstan-ignore-next-line
					$output = "[" . $nodeElem->getAttribute("alt") . "]";
				} else {
					$output = "";
				}
				break;
			case "li":
				$output .= "\n";
				break;
			case "blockquote":
				// process quoted text for whitespace/newlines
				$output = self::processWhitespaceNewlines($output);
				// add leading newline
				$output = "\n" . $output;
				// prepend '> ' at the beginning of all lines
				$output = preg_replace("/\n/im", "\n> ", $output);
				// replace leading '> >' with '>>'
				$output = preg_replace("/\n> >/im", "\n>>", $output);
				// add another leading newline and trailing newlines
				$output = "\n" . $output . "\n\n";
				break;
			default:
				// do nothing
		}
		return $output;
	}

}
