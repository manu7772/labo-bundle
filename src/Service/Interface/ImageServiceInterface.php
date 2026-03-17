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
    public function getLiipFilters(bool $getArray = false, ?string $filter = null): FilterConfiguration|array;
    public function getLiipFilter(string $filter): ?array;
    public function getLiipFiltersNames(?string $filter = null): array;
    public function isAvailableLiipFilter(string $filter): bool;
    public function getDefaultLiipFilterName(null|string|object $entity = null): ?string;
    public function getDefaultLiipFilterChoiceArea(null|string|object $entity = null): array;
    public function getLiipFilterChoices(int $min_width = 0, int $min_height = 0, null|string|object $entity = null, bool $exclude_admins = true): array;
}