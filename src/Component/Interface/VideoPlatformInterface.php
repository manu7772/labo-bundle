<?php
namespace Aequation\LaboBundle\Component\Interface;

use Aequation\LaboBundle\Component\video\VPlatformOdysee;
use Aequation\LaboBundle\Component\video\VPlatformYoutube;

interface VideoPlatformInterface
{
    public const VIDEO_DEFAULT_TITLE = 'Video sans titre';
    public const ALL_CLASSES = [
        VPlatformYoutube::class,
        VPlatformOdysee::class,
    ];

    public function __construct(?string $url = null);
    public function isValid(): bool;
    public function isAlive(): bool;
    public function isIdValid(): bool;
    public function setId(string $id): static;
    public function getId(): ?string;
    public function getThumbnail(?string $quality = null): ?string;
    public function getThumbnailQualitys(): array;
    public function getTitle(): string;
    // public function getTitleFromWeb(): string;
    // public function setTitle(string $title): static;
    public function getUrl(): ?string;
    public function setUrl(string $url): static;
    public function isUrlValid(): bool;
    public function getGeneratedUrl(): ?string;

}