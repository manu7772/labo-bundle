<?php
namespace Aequation\LaboBundle\Component\video;

use Twig\Markup;
use Aequation\LaboBundle\Service\Tools\Strings;

class VPlatformYoutube extends VideoPlatform
{
    public const ENABLED = true;
    public const NAME = 'youtube';
    public const LABEL = 'YouTube';
    public const HOSTS = ['www.youtube.com', 'youtube.com', 'youtu.be', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'];
    public const ID_REGEX = '#^[\w\-]{11}$#i';
    public const VIDEO_URL_TEMPLATE = 'https://www.youtube.com/watch?v=%s';
    public const IFRAME_TEMPLATE = '<iframe class="%s" src="https://www.youtube.com/embed/%s" title="%s" frameborder="0" allow="%s" referrerpolicy="strict-origin-when-cross-origin"%s></iframe>';
    // public const THUMBNAIL_URL_TEMPLATE = 'https://img.youtube.com/vi/%s/%s.jpg'; // default, mqdefault, hqdefault, sddefault, maxresdefault
    public const THUMBNAIL_URL_TEMPLATE = 'https://i.ytimg.com/vi/%s/%s.jpg'; // default, mqdefault, hqdefault, sddefault, maxresdefault
    public const THUMBNAIL_QUALITYS = ['hqdefault', 'default', 'mqdefault', 'sddefault', 'maxresdefault'];
    public const ICON = 'tabler:brand-youtube-filled';
    public const COLOR = 'red';


    /** ID *****************************************************************************************************************************************/

    public static function extractIdFromUrl(string $url): ?string
    {
        $parsed_url = parse_url($url);
        parse_str($parsed_url['query'] ?? '', $query_params);
        // dump($parsed_url, $query_params);
        switch (true) {
            case static::testId($query_params['v'] ?? ''):
                return $query_params['v'];
                break;
            case preg_match('#^youtu.be$#', $parsed_url['host'] ?? ''):
                $paths = explode('/', trim($parsed_url['path'] ?? '', '/'));
                return end($paths) ?: null;
                break;
            case strlen($parsed_url['path'] ?? '') > 0:
                $paths = explode('/', trim($parsed_url['path'] ?? '', '/'));
                return end($paths) ?: null;
                break;
            default:
                return null;
                break;
        }
    }

    public function getTitle(): string
    {
        $parent_title = parent::getTitle();
        return $this->title = $parent_title === $this->getTitleFromWeb()
            ? preg_replace('/\s*-\s*YouTube$/', '', $parent_title)
            : $parent_title;
    }

    public function getIframe(array $options = []): ?Markup
    {
        if(empty(static::IFRAME_TEMPLATE) || !$this->isIdValid()) return null;
        // Defaults
        if(is_array($options['class'] ??= 'w-full aspect-video')) {
            $options['class'] = implode(' ', $options['class']);
        }
        if(is_array($options['allow'] ??= 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share')) {
            $options['allow'] = implode('; ', $options['allow']);
        }
        $options['allowfullscreen'] ??= true;
        // Generate
        $iframe = sprintf(
            static::IFRAME_TEMPLATE,
            $options['class'],
            $this->getId(),
            htmlspecialchars($this->getTitle().' - Youube', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $options['allow'],
            $options['allowfullscreen'] ? ' allowfullscreen' : ''
        );
        return Strings::markup($iframe);
    }

    /** THUMBNAIL **********************************************************************************************************************************/

    // public function getThumbnail(?string $id, ?string $quality = null): ?string
    // {
    //     return empty(static::THUMBNAIL_URL_TEMPLATE)
    //         ? null
    //         : vsprintf((string) static::THUMBNAIL_URL_TEMPLATE, [$quality ?? static::THUMBNAIL_QUALITYS[0] ?? '', $id]);
    // }

}