<?php
namespace Aequation\LaboBundle\Component\video;

use Aequation\LaboBundle\Component\Interface\VideoPlatformBuilderInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Component\Interface\VideoPlatformInterface;
// PHP
use ReflectionClass;
use InvalidArgumentException;

class VideoPlatformBuilder implements VideoPlatformBuilderInterface
{
    public const AUTO_VIDEO_TYPE = [
        'label' => 'Auto detection',
        'name' => 'auto_determine',
        'icon' => 'tabler:world-search',
    ];

    public static function new(string $platform_or_url): ?VideoPlatformInterface
    {
        return static::findVideoPlatform($platform_or_url);
    }

    public static function findVideoPlatform(string $platform_or_url): ?VideoPlatformInterface
    {
        if(!Encoders::isUrl($platform_or_url)) {
            // It's a platform name
            foreach (static::getVideoPlatformClasses() as $rc) {
                /** @var ReflectionClass $rc */
                if(in_array($platform_or_url, [$rc->getConstant('NAME'), $rc->getConstant('LABEL')], true)) {
                    return $rc->newInstance();
                }
            }
        } else {
            // It's a URL
            foreach (static::getVideoPlatformClasses() as $rc) {
                /** @var ReflectionClass $rc */
                if($rc->name::testUrl($platform_or_url)) {
                    /** @var VideoPlatformInterface $vp */
                    $vp = $rc->newInstance($platform_or_url);
                    if($vp->isValid()) {
                        return $vp;
                    } else {
                        return null;
                        // throw new InvalidArgumentException(vsprintf('Error %s line %d: The URL "%s" is not valid for video platform %s.', [__FILE__, __LINE__, $platform_or_url, $rc->getConstant('LABEL')]));
                    }
                } else {
                    dump('No match for '.$rc->getConstant('LABEL'));
                }
            }
        }
        throw new InvalidArgumentException(vsprintf('Error %s line %d: No video platform found for "%s".', [__FILE__, __LINE__, $platform_or_url]));
    }

    public static function getVideoPlatformClasses(): array
    {
        return Classes::getInheritedClasses(VideoPlatform::class, false, VideoPlatform::ALL_CLASSES, true);
    }

    public static function getPlatformChoices(bool $filter = true, bool $icons = true): array
    {
        $choices = [
            static::getLabelWithIcon(static::AUTO_VIDEO_TYPE['label'], static::AUTO_VIDEO_TYPE['icon'] ?? null) => static::AUTO_VIDEO_TYPE['name'],
        ];
        foreach (static::getVideoPlatformClasses() as $rc) {
            /** @var ReflectionClass $rc */
            if(!$filter || $rc->getConstant('ENABLED')) {
                $choices[($icons ? '<twig:ux:icon name="'.$rc->getConstant('ICON').'" style="color: '.$rc->getConstant('COLOR').'; height: 1.2em; width: 1.2em; margin-right: 0.5em;" /> ' : null).$rc->getConstant('LABEL')] = $rc->getConstant('NAME');
            }
        }
        return $choices;
    }

    protected static function getLabelWithIcon(string $label, ?string $icon = 'tabler:video-filled', string $color = 'gray', ?ReflectionClass $rc = null): string
    {
        if($rc) {
            $icon = $rc->getConstant('ICON');
            $color = $rc->getConstant('COLOR');
        }
        return (!empty($icon) ? '<twig:ux:icon name="'.$icon.'" style="color: '.$color.'; height: 1.2em; width: 1.2em; margin-right: 0.5em;" /> ' : null).$label;
    }

    public static function getIcon(?string $icon = null, ?string $color = null): string
    {
        return '<twig:ux:icon name="'.($icon ?? VideoPlatform::ICON ?? 'tabler:video-filled').'" style="color: '.($color ?? VideoPlatform::COLOR ?? 'gray').'; height: 1.2em; width: 1.2em; margin-right: 0.5em;" />';
    }

    public static function getWebsiteTypeIcon(?string $color = null): string
    {
        return static::getIcon(VideoPlatform::ICON_WEBSITE, $color);
    }

}