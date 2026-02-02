<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Component\ImageInfo;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
// Symfony
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

interface ImageServiceInterface extends ItemServiceInterface
{
    public function getAppService(): AppServiceInterface;
    public function getVichHelper(): UploaderHelper;
    public function getLiipCache(): CacheManager;
    public static function estimateRatio(int $x, int $y): string;
    public function getImageInfo(object|array $image, null|string|false $liipfilter = null, $resolver = null, bool $generate = true): ImageInfo;
    // public function store(ImageInterface $image, string $liipfilter): void;
    public function generateFilteredImage(string|ImageInterface $imageOrPath, string $liipfilter, $resolver = null): ?string;
    public function getBrowserPath(ImageInterface $image, ?string $filter = null, array $runtimeConfig = [], $resolver = null, $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string;
    public function getLiipFilters(): FilterConfiguration;
    public function getLiipFiltersNames(): array;
    public function isAvailableLiipFilter(?string $filter): bool;
}