<?php
namespace Aequation\LaboBundle\Component\video;

// Symfony
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Component\Interface\VideoPlatformInterface;
use Aequation\LaboBundle\Service\Tools\Strings;
// PHP
use DOMDocument;
use InvalidArgumentException;
use Twig\Markup;

abstract class VideoPlatform implements VideoPlatformInterface
{
    public const ENABLED = true;
    public const NAME = null;
    public const LABEL = null;
    public const HOSTS = []; // Optional, used in testUrl
    public const ID_REGEX = null;
    public const VIDEO_URL_TEMPLATE = null;
    public const THUMBNAIL_URL_TEMPLATE = null; // Use method
    public const THUMBNAIL_QUALITYS = []; // No qualitys
    public const IFRAME_TEMPLATE = '<iframe class="%s" src="https://www.youtube.com/embed/%s" title="%s" frameborder="0" allow="%s" referrerpolicy="strict-origin-when-cross-origin"%s></iframe>';
    public const ICON = 'tabler:video-filled';
    public const COLOR = 'gray';

    public string $url; // Original given URL
    public ?DOMDocument $source_code; // Raw HTML source code of the video page
    public string $id; // Video ID
    public string $title; // Video title
    protected bool $alive; // Is the video alive (tested once)

    public function __construct(?string $url = null)
    {
        $this->setUrl((string) $url);
        // dump($this->getTitle());
    }


    /***********************************************************************************************************************************************/
    /** VALIDITY */
    /***********************************************************************************************************************************************/

    public function isValid(): bool
    {
        return $this->isIdValid() && $this->getSourceCode();
    }

    public function isAlive(): bool
    {
        return $this->alive ??= !empty($this->getSourceCode());
    }


    /***********************************************************************************************************************************************/
    /** METHODS */
    /***********************************************************************************************************************************************/

    public static function getName(): ?string
    {
        return static::NAME;
    }

    public static function getLabel(): ?string
    {
        return static::LABEL;
    }

    public static function isEnabled(): bool
    {
        return static::ENABLED;
    }


    /** SOURCE CODE *****************************************************************************************************************************************/

    protected function getSourceCode(
        bool $force = false
    ): ?DOMDocument
    {
        if(!isset($this->source_code) || $force) {
            if($url = $this->getGeneratedUrl()) {
                libxml_use_internal_errors(true);
                $this->source_code = new DOMDocument();
                $this->source_code->loadHTMLFile($url);
                // $errors = libxml_get_errors();
                // foreach ($errors as $error) {
                //     // Handle each error
                //     print_r($error->message.PHP_EOL);
                // }
                libxml_clear_errors();
                libxml_use_internal_errors(false);
            } else {
                $this->source_code = null;
            }
        }
        return $this->source_code;
    }


    /** ID *****************************************************************************************************************************************/

    abstract public static function extractIdFromUrl(string $url): ?string;

    protected function extractId(): ?string
    {
        return static::extractIdFromUrl($this->url);
    }

    public static function testId(string $id): bool
    {
        return preg_match((string) static::ID_REGEX, $id);
    }

    public function isIdValid(): bool
    {
        return static::testId($this->id ?? '');
    }

    public function setId(string $id): static
    {
        if(!static::testId($id)) {
            throw new InvalidArgumentException(vsprintf('Error %s line %d: L\'identifiant "%s" pour la vidéo de type %s n\'est pas valide.', [__FILE__, __LINE__, $id, static::NAME]));
        }
        if($id !== ($this->id ?? null)) {
            $this->id = $id;
            unset($this->alive);
            unset($this->source_code);
        }
        return $this;
    }

    public function getId(): ?string
    {
        if(!isset($this->id) && $id = $this->extractId()) {
            $this->id = $id;
        }
        return $this->id ?? null;
    }


    /** THUMBNAIL **********************************************************************************************************************************/

    public function getThumbnail(?string $quality = null): ?string
    {
        if(!$this->isIdValid() || empty(static::THUMBNAIL_URL_TEMPLATE)) return null;
        if(!empty($quality) && !empty(static::THUMBNAIL_QUALITYS) && !in_array($quality, static::THUMBNAIL_QUALITYS, true)) {
            $quality = null;
            // throw new InvalidArgumentException(vsprintf('Error %s line %d: La qualité "%s" pour la miniature de la vidéo de type %s n\'est pas valide. Utilisez l\'une des valeurs suivantes : %s', [__FILE__, __LINE__, $quality, static::NAME, implode(', ', static::THUMBNAIL_QUALITYS)]));
        }
        $data = count(static::THUMBNAIL_QUALITYS) > 0 ? [$this->id, $quality ?? static::THUMBNAIL_QUALITYS[0]] : [$this->id];
        return vsprintf((string) static::THUMBNAIL_URL_TEMPLATE, $data);
    }

    public function getThumbnailQualitys(): array
    {
        return static::THUMBNAIL_QUALITYS;
    }


    /** TITLE **************************************************************************************************************************************/

    public function getTitle(): string
    {
        if(!Strings::hasText($this->title ?? null) || $this->title === static::VIDEO_DEFAULT_TITLE) {
            $this->title = $this->getTitleFromWeb();
        }
        return Strings::textOrNull($this->title, false) ?: static::VIDEO_DEFAULT_TITLE;
    }

    /** @see https://onlinephp.io/c/3a878 */
    public function getTitleFromWeb(): string
    {
        // return static::VIDEO_DEFAULT_TITLE;
        $title = $this->getSourceCode()?->getElementsByTagName('title')?->item(0)?->nodeValue ?: null;
        $title = trim(iconv('utf-8', 'latin1', $title ?? ''));
        return Strings::textOrNull($title, false) ?: static::VIDEO_DEFAULT_TITLE;
    }

    public function setTitle(string $title): static
    {
        $this->title = Strings::textOrNull($title, false) ?: static::VIDEO_DEFAULT_TITLE;
        return $this;
    }


    /** URL ****************************************************************************************************************************************/

    public static function testUrl(string $url): bool
    {
        $parsed_url = parse_url($url);
        if(!isset($parsed_url['host']) || !in_array($parsed_url['host'], static::HOSTS, true)) {
            dump('Host '.$parsed_url['host'].' not found in '.implode(', ', static::HOSTS));
            return false;
        }
        // dump('Try extract ID from URL '.$url.': '.json_encode(static::extractIdFromUrl($url)));
        return !empty(static::extractIdFromUrl($url));
    }

    public function getUrl(): ?string
    {
        return $this->url ?? $this->getGeneratedUrl();
    }

    public function setUrl(string $url): static
    {
        if(!Encoders::isUrl($url)) {
            return $this;
            // throw new InvalidArgumentException(vsprintf('Error %s line %d: L\'URL "%s" pour la vidéo de type %s n\'est pas valide.', [__FILE__, __LINE__, $url, static::NAME]));
        }
        $this->url = $url;
        if($id = $this->extractId()) $this->id = $id;
        return $this;
    }

    public function isUrlValid(): bool
    {
        return static::testUrl((string) $this->getUrl());
    }

    public static function generateUrl(string $id): ?string
    {
        return !empty(static::VIDEO_URL_TEMPLATE)
            ? vsprintf((string) static::VIDEO_URL_TEMPLATE, [$id])
            : null;
    }

    public function getGeneratedUrl(): ?string
    {
        return $this->isIdValid() ? static::generateUrl($this->id) : null;
    }

    abstract public function getIframe(array $options = []): ?Markup;


    /***********************************************************************************************************************************************/
    /** STATIC METHODS */
    /***********************************************************************************************************************************************/

    protected static function getLabelWithIcon(string $label, ?string $icon = 'tabler:video-filled', string $color = 'gray'): string
    {
        return (!empty($icon) ? static::getIcon($color).' ' : null).$label;
    }

    public static function getIcon(?string $icon = null, ?string $color = null): string
    {
        return '<twig:ux:icon name="'.($icon ?? static::ICON ?? 'tabler:video-filled').'" style="color: '.($color ?? static::COLOR ?? 'gray').'; height: 1.2em; width: 1.2em; margin-right: 0.5em;" />';
    }

}