<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;

use DOMDocument;
use DOMXPath;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Twig\Markup;

class HtmlDom extends BaseService
{

    /*************************************************************************************
     * DOM DOCUMENT
     *************************************************************************************/

    public static function getDomDocument(
        string $html,
        int $options = 0
    ): ?DOMDocument
    {
        $doc = new DOMDocument();
        $result = $doc->loadHTML($html, $options);
        return $result
            ? $doc
            : null;
    }

    public static function extractFromHtml(
        string $html,
        string $search,
        bool $getAsMarkup = false, // or use Strings::markdup() with result of method [returned]->saveHTML()
    ): DOMDocument|Markup|null
    {
        $doc = static::getDomDocument($html);
        if($doc instanceof DOMDocument) {
            $xpath = new DOMXPath($doc);
            $html = $xpath->query($search);
            if($html) {
                $temp_dom = new DOMDocument();
                foreach($html as $n) $temp_dom->appendChild($temp_dom->importNode($n,true));
                $html = $temp_dom;
                return $getAsMarkup
                    ? Strings::markup($html->saveHTML())
                    : $html;
            }
        }
        return null;
    }

    public static function isValidHtml(
        string $html
    ): bool
    {
        return static::getDomDocument($html) instanceof DOMDocument;
    }

    public static function toHtmlAttributes(
        ?array $attributes
    ): string
    {
        $string = '';
        if(empty($attributes)) return $string;
        foreach ($attributes as $name => $values) {
            switch (true) {
                case is_array($values) && $name === "style":
                    $css = [];
                    foreach ($values as $key => $value) {
                        $css[] = (is_string($key) ? $key.':' : '').$value;
                    }
                    $string .= ' '.$name.'="'.implode('; ', $css).'"';
                    break;
                case is_array($values):
                    $string .= ' '.$name.'="'.implode(' ', $values).'"';
                case is_string($values):
                    $string .= ' '.$name.'="'.$values.'"';
                    break;
                default:
                    // dd($name, $values);
                    $string .= ' '.$name.'="'.$values.'"';
                    break;
            }
        }
        return $string;
    }


}