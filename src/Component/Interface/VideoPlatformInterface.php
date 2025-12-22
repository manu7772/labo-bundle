<?php
namespace Aequation\LaboBundle\Component\Interface;

use Aequation\LaboBundle\Component\video\VPlatformOdysee;
use Aequation\LaboBundle\Component\video\VPlatformYoutube;
// PHP
use Twig\Markup;

interface VideoPlatformInterface
{
    public const VIDEO_DEFAULT_TITLE = 'Video sans titre';
    public const ALL_CLASSES = [
        VPlatformYoutube::class,
        VPlatformOdysee::class,
    ];
    public const WEBSITE_PLATFORM = 'website';

    public function __construct(?string $url = null);
    public function isValid(): bool;
    public function isAlive(): bool;
    public static function getName(): ?string;
    public static function getLabel(): ?string;
    public static function isEnabled(): bool;
    public static function extractIdFromUrl(string $url): ?string;
    public static function testId(string $id): bool;
    public function isIdValid(): bool;
    public function setId(string $id): static;
    public function getId(): ?string;
    public function getThumbnail(?string $quality = null): ?string;
    public function getThumbnailQualitys(): array;
    public function getTitle(): string;
    public static function testUrl(string $url): bool;
    public function getUrl(): ?string;
    public function setUrl(string $url): static;
    public function isUrlValid(): bool;
    public static function generateUrl(string $id): ?string;
    public function getGeneratedUrl(): ?string;
    public function getIframe(array $options = []): ?Markup;
    public static function getIcon(?string $icon = null, ?string $color = null): string;

}