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

    public const DEFAULT_LIIP_FILTER = '#^normal_(x|w)800$#';
    public const DEFAULT_LIIP_FILTER_CHOICES_AREA = [800, 800];
    public const THUMBNAIL_LIIP_FILTER = '#^thumbnail_q$#';

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

    /**
     * Get the entire filter configuration or an array of filters depending on the $getArray parameter.
     * 
     * @param bool $getArray
     * @param ?string $filter = null
     * @return FilterConfiguration|array
     */
    public function getLiipFilters(bool $getArray = false, ?string $filter = null): FilterConfiguration|array
    {
        $filters = $getArray || $filter !== null ? $this->filterConfig->all() : $this->filterConfig;
        if($filter !== null) {
            // dump($filters, $filter);
            $filters = array_filter($filters, fn($filtername) => $filter === $filtername || @preg_match($filter, $filtername), ARRAY_FILTER_USE_KEY);
        }
        return $filters;
    }

    /**
     * Get the configuration of a specific liip filter by its name, returning null if the filter is not available in the configuration.
     * 
     * @param string|null $filter The name of the liip filter to retrieve the configuration for
     * @return array|null
     */
    public function getLiipFilter(string $filter): ?array
    {
        $filters = $this->getLiipFilters(true, $filter);
        return empty($filters) ? null : reset($filters);
    }

    /**
     * Get the names of all available liip filters from the filter configuration.
     * 
     * @return array
     */
    public function getLiipFiltersNames(?string $filter = null): array
    {
        $filters = $this->getLiipFilters(true, $filter);
        return array_keys($filters);
    }

    /**
     * Check if a given liip filter name is available in the filter configuration.
     * 
     * @param string $filter
     * @return bool
     */
    public function isAvailableLiipFilter(string $filter): bool
    {
        return !empty($this->getLiipFilters(true, $filter));
    }

    /**
     * Get the default liip filter name for a given entity, checking if the entity has a specific default filter defined and if it is available, otherwise falling back to the service's default filter or the first available filter in the configuration. The method ensures that the returned filter name is valid and can be used for image processing.
     * 
     * @param null|string|object $entity Optional entity to check for a specific default filter (if it implements ImageInterface)
     * @return string|null The default liip filter name to use for the given entity, or null if no valid filter is found
     */
    public function getDefaultLiipFilterName(null|string|object $entity = null): ?string
    {
        $filtername = is_a($entity, ImageInterface::class, true) && $this->isAvailableLiipFilter($entity::getDefaultLiipFilter() ?? '') ? $entity::getDefaultLiipFilter() : static::DEFAULT_LIIP_FILTER;
        if(!$this->isAvailableLiipFilter($filtername)) {
            $area = $this->getDefaultLiipFilterChoiceArea($entity);
            $list = $this->getLiipFilterChoices($area[0], $area[1], $entity, true);
            if(count($list) > 0) {
                $filtername = array_key_first($list);
            }
        }
        return $filtername;
    }

    public function getDefaultLiipFilterChoiceArea(null|string|object $entity = null): array
    {
        $area = is_a($entity, ImageInterface::class, true) ? $entity::getDefaultLiipFilterChoiceArea() : static::DEFAULT_LIIP_FILTER_CHOICES_AREA;
        return $area;
    }

    /**
     * Get available liip filters as choices for form fields, with optional filtering by minimum width and height, and by entity available filters if specified. Admin filters can be excluded by setting $exclude_admins to true.
     * 
     * @param int $min_width Minimum width of the filters to include in the choices
     * @param int $min_height Minimum height of the filters to include in the choices
     * @param null|string|object $entity Optional entity to filter the choices by its available filters (if it implements ImageInterface
     * @param bool $exclude_admins Whether to exclude admin filters (filters with names starting with 'admin_' or 'ea_') from the choices
     * @return array An associative array of filter names as keys and filter keys as values, suitable for use as choices in form fields
     */
    public function getLiipFilterChoices(int $min_width = 0, int $min_height = 0, null|string|object $entity = null, bool $exclude_admins = true): array
    {
        $choices = [];
        foreach ($this->filterConfig->all() as $key => $value) {
            if(!$exclude_admins || preg_match('/^(?!admin_|ea_).+$/i', $key)) {
                $available = true;
                foreach ($value['filters'] ?? [] as $name => $filter) {
                    switch ($name) {
                        case 'scale':
                            $filter_width = $filter['dim'][0] ?? 0;
                            $filter_height = $filter['dim'][1] ?? 0;
                            break;
                        case 'upscale':
                            $filter_width = $filter['min'][0] ?? 0;
                            $filter_height = $filter['min'][1] ?? 0;
                            break;
                        case 'downscale':
                            $filter_width = $filter['max'][0] ?? 0;
                            $filter_height = $filter['max'][1] ?? 0;
                            break;
                        case 'relative_resize':
                            $filter_width = $filter['widen'] ?? 0;
                            $filter_height = $filter['heighten'] ?? 0;
                            break;
                        case 'background':
                            $filter_width = $filter['size'][0] ?? 0;
                            $filter_height = $filter['size'][1] ?? 0;
                            break;
                        default:
                            $filter_width = 0;
                            $filter_height = 0;
                            break;
                    }
                    if($filter_width < $min_width || $filter_height < $min_height) {
                        $available = false;
                    }
                }
                if($available) {
                    $choices['liip_names.'.$key] = $key;
                }
            }
        }
        if(!empty($entity) && count($choices) > 0) {
            // Filter by entity available filters if specified
            $entity_filters = is_a($entity, ImageInterface::class) ? $entity::getAvailableLiipFilters() ?? [] : true;
            if(is_array($entity_filters)) {
                $choices = array_filter($choices, fn ($filter) => in_array($filter, $entity_filters));
            }
        }
        return $choices;
    }

}