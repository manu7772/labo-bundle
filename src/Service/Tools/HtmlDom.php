<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;
// PHP
use Twig\Markup;
use DOMDocument;
use DOMXPath;

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
    ): Markup
    {
        // dump($attributes);
        $attrs = [];
        if(empty($attributes)) return Strings::markup('');
        foreach ($attributes as $name => $values) {
            if(!empty($values) || is_bool($values)) {
                // dump($name, $values);
                switch (true) {
                    case !is_string($name) && is_string($values):
                        $attrs[] = $values.'="true"';
                        break;
                    case $name === "style":
                        $css = [];
                        if(is_array($values)) {
                            foreach ($values as $key => $value) {
                                $css[] = (is_string($key) ? $key.':' : '').$value;
                            }
                        } else if(is_string($values)) {
                            $css[] = $values;
                        }
                        if(!empty($css)) {
                            $attrs[] = $name.'="'.implode('; ', $css).'"';
                        }
                        break;
                    case $name === "class":
                        $attrs[] = $name.'="'.(is_array($values) ? implode(' ', $values) : $values).'"';
                        break;
                    case is_array($values):
                        $attrs[] = $name.'="'.implode(' ', $values).'"';
                        break;
                    case is_string($values):
                        $attrs[] = $name.'="'.$values.'"';
                        break;
                    case is_bool($values) && is_string($name):
                        $attrs[] = $name.'="'.($values ? 'true' : 'false').'"';
                        break;
                    default:
                        // dd($name, $values);
                        $attrs[] = $name.'="'.$values.'"';
                        break;
                }
            }
        }
        $attributes = empty($attrs) ? '' : ' '.implode(' ', $attrs);
        return Strings::markup($attributes);
    }


}