<?php
namespace Aequation\LaboBundle\Component\video;

use Twig\Markup;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Tools\Encoders;

/**
 * examples
 * @see https://odysee.com/@prunedeprune:a/008-Retout-Sacte-Assassins-1:7
 */

class VPlatformOdysee extends VideoPlatform
{
    public const ENABLED = true;
    public const NAME = 'odysee';
    public const LABEL = 'Odysee';
    public const HOSTS = ['www.odysee.com', 'odysee.com'];
    public const URL_EXTRACT_ID = null; // Use method
    public const ID_REGEX = '#^[\w\-:]{8,}$#i';
    public const VIDEO_URL_TEMPLATE = 'https://odysee.com/%s';
    public const THUMBNAIL_URL_TEMPLATE = null; // Use method
    public const THUMBNAIL_QUALITYS = []; // No qualitys
    // public const ICON = 'tabler:video-filled';
    public const COLOR = 'orange';


    /** ID *****************************************************************************************************************************************/

    public static function extractIdFromUrl(string $url): ?string
    {
        $parsed_url = parse_url($url);
        parse_str($parsed_url['query'] ?? '', $query_params);
        dump($parsed_url, $query_params);
        switch (true) {
            case static::testId($query_params['v'] ?? ''):
                return $query_params['v'];
                break;
            default:
                return null;
                break;
        }
    }

    public function getIframe(array $options = []): ?Markup
    {
        return null;
    }

}