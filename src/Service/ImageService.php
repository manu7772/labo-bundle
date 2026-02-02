<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\ImageInfo;
use Throwable;
use Liip\ImagineBundle\Model\Binary;
use Aequation\LaboBundle\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Liip\ImagineBundle\Service\FilterService;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\ImageServiceInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

#[AsAlias(ImageServiceInterface::class, public: true)]
class ImageService extends ItemService implements ImageServiceInterface
{
    public const ENTITY = Image::class;

    public const RATIO_LIMIT = 1.5;
    public const WIDTH_LIMIT = 800;
    public const IMG_FORMAT_LANDSCAPE = 'landscape';
    public const IMG_FORMAT_PORTRAIT = 'portrait';
    public const IMG_FORMAT_SQUARE = 'square';
    public const IMG_FORMAT_UNKNOWN = 'unknown';

    public function __construct(
        protected EntityManagerInterface $em,
        protected AppServiceInterface $appService,
        protected AccessDecisionManagerInterface $accessDecisionManager,
        protected ValidatorInterface $validator,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache,
        #[Autowire(service: 'liip_imagine.filter.configuration')]
        protected FilterConfiguration $filterConfig,
        protected FilterService $filterService,
    )
    {
        parent::__construct($em, $appService, $accessDecisionManager, $validator);
    }

    public function getAppService(): AppServiceInterface
    {
        return $this->appService;
    }

    public function getVichHelper(): UploaderHelper
    {
        return $this->vichHelper;
    }

    public function getLiipCache(): CacheManager
    {
        return $this->liipCache;
    }

    public static function estimateRatio(int $x, int $y): string
    {
        if($x > $y * static::RATIO_LIMIT && $x >= static::WIDTH_LIMIT) {
            return self::IMG_FORMAT_LANDSCAPE;
        }
        if($y > $x * static::RATIO_LIMIT) {
            return self::IMG_FORMAT_PORTRAIT;
        }
        return self::IMG_FORMAT_SQUARE;
    }

    public function getImagePathInfo(?string $path): array
    {
        $corrected_path = @file_exists($path) ? $path : $this->appService->getDir('public/'.ltrim($path, DIRECTORY_SEPARATOR));
        $path_info = @pathinfo($corrected_path, PATHINFO_ALL);
        if(Encoders::isUrl($corrected_path)) {
            $url_parts = parse_url($corrected_path);
            $path_info = pathinfo($url_parts['path']);
        }
        $path_info['requested_path'] = $corrected_path;
        $path_info['file_exists'] = @file_exists($corrected_path);
        $path_info['created_at'] = null;
        $path_info['modified_at'] = null;
        if($path_info['file_exists']) {
            $creation_date = filectime($corrected_path);
            $path_info['created_at'] = date("F d Y H:i:s", $creation_date);
            $modification_date = filemtime($corrected_path);
            $path_info['modified_at'] = date("F d Y H:i:s", $modification_date);
        }
        return $path_info;
    }

    public function getImageInfo(object|array $image, null|string|false $liipfilter = null, $resolver = null, bool $generate = true): ImageInfo
    {
        return new ImageInfo($this, $image, $liipfilter, $resolver);
    }

    public function generateFilteredImage(string|ImageInterface $imageOrPath, ?string $liipfilter = null, $resolver = null): ?string
    {
        $path = $imageOrPath instanceof ImageInterface ? $this->vichHelper->asset($imageOrPath) : $imageOrPath;
        try {
            return $this->filterService->getUrlOfFilteredImage($path, $liipfilter ?? ($imageOrPath instanceof ImageInterface ? $imageOrPath->getLiipDefaultFilter() : 'default'), $resolver);
        } catch (Throwable $th) {
            return null;
        }
    }

    public function getBrowserPath(
        ImageInterface $image,
        ?string $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): string
    {
        $url = $this->vichHelper->asset($image);
        if($filter) {
            $url = $this->liipCache->getBrowserPath($url, $filter, $runtimeConfig, $resolver, $referenceType);
        }
        return $url;
    }

    public function getLiipFilters(): FilterConfiguration
    {
        return $this->filterConfig;
    }

    public function getLiipFilter(?string $filter): ?array
    {
        return $this->isAvailableLiipFilter($filter) ? $this->filterConfig->get($filter) : null;
    }

    public function getLiipFiltersNames(): array
    {
        return array_keys($this->filterConfig->all());
    }

    public function isAvailableLiipFilter(?string $filter): bool
    {
        return array_key_exists($filter, $this->filterConfig->all());
    }

}