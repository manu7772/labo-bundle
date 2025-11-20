<?php
namespace Aequation\LaboBundle\Component\Interface;


interface VideoPlatformBuilderInterface
{
    public static function new(string $platform_or_url): ?VideoPlatformInterface;
    public static function findVideoPlatform(string $platform_or_url): ?VideoPlatformInterface;
    public static function getVideoPlatformClasses(): array;
    public static function getPlatformChoices(bool $filter = true, bool $icons = true): array;
    public static function getIcon(?string $icon = null, ?string $color = null): string;
    public static function getWebsiteTypeIcon(?string $color = null): string;
}