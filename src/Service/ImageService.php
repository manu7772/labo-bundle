<?php
namespace Aequation\LaboBundle\Service;

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

    public function getImageInfo(?ImageInterface $image, null|string|false $liipfilter = null, $resolver = null, bool $generate = true): array
    {
        $path = $image ? $this->vichHelper->asset($image) : null;
        $info = [
            'format' => self::IMG_FORMAT_UNKNOWN,
            'width' => null,
            'height' => null,
            'filter' => empty($liipfilter) ? null : $liipfilter,
            // 'size' => null,
            // 'mime' => null,
            // 'extension' => $path_info['extension'] ?? null,
            'path' => $path,
            'url' => null,
            'stored' => false,
        ];

        if(!$image) {
            // return $info;
        } else if(false === $liipfilter) {
            // No filter, use original dimensions
            $imgsize = $image->getDimensions(true);
            if($imgsize) {
                // Infos from Image entity
                $info['format'] = static::estimateRatio($imgsize[0], $imgsize[1]);
                $info['width'] = $imgsize[0];
                $info['height'] = $imgsize[1];
            } else {
                // No dimensions stored, get from file
                $url = trim($path, '/');
                if($imgsize = @getimagesize($url)) {
                    $info['format'] = static::estimateRatio($imgsize[0], $imgsize[1]);
                    $info['width'] = $imgsize[0];
                    $info['height'] = $imgsize[1];
                    // $info['size'] = @filesize($url);
                    // $info['mime'] = $dimensions['mime'] ?? null;
                }
            }
            return $info;
        } else {
            if(empty($liipfilter)) {
                // No filter defined, use image default filter
                $liipfilter = $image->getImagefilter() ?? $image->getLiipDefaultFilter();
            }
            $info['filter'] = $liipfilter;
            // Filter defined, get dimensions from filtered image
            // $info['stored'] = $this->liipCache->isStored($path, $liipfilter, $resolver);
            // if(!$info['stored'] && $generate) {
            //     $info['url'] = $this->generateFilteredImage($path, $liipfilter, $resolver);
            //     $info['stored'] = $this->liipCache->isStored($path, $liipfilter, $resolver);
            // } else {
            //     $info['url'] = $this->generateFilteredImage($path, $liipfilter, $resolver);
            //     // $info['url'] = $this->liipCache->generateUrl($path, $liipfilter, [], $resolver, UrlGeneratorInterface::ABSOLUTE_URL);
            // }
            $info['url'] = $this->generateFilteredImage($path, $liipfilter, $resolver);
            $info['stored'] = $this->liipCache->isStored($path, $liipfilter, $resolver);
            if(!$info['stored']) {
                return $info;
            }
            $liipfilterPath = $this->liipCache->resolve($path, $liipfilter, $resolver);
            if(Encoders::isUrl($liipfilterPath)) {
                // Url
                $liipfilterUrl = parse_url($liipfilterPath);
                $file_info = pathinfo($liipfilterUrl['path']);
            } else {
                // Path
                $file_info = pathinfo($liipfilterPath);
            }
            $liipfilterPath = trim($file_info['dirname'].DIRECTORY_SEPARATOR.$file_info['basename'], '/');
            if(@file_exists($liipfilterPath) && $imgsize = @getimagesize($liipfilterPath)) {
                $info['format'] = static::estimateRatio($imgsize[0], $imgsize[1]);
                $info['width'] = $imgsize[0];
                $info['height'] = $imgsize[1];
                // $info['size'] = @filesize($url);
                // $info['mime'] = $imgsize['mime'] ?? null;
            }
        }
        // dump($info);
        return $info;
    }

    // protected function store(ImageInterface $image, string $liipfilter, ?string $mime = null): void
    // {
    //     $url = trim($this->vichHelper->asset($image), '/');
    //     // dump($url.' => '.json_encode(@file_exists($url)));
    //     $binary = new Binary(file_get_contents($url), $mime ?? $image->getMime());
    //     // dump($binary);
    //     $this->liipCache->store($binary, $url, $liipfilter);
    // }

    public function generateFilteredImage(string|ImageInterface $imageOrPath, string $liipfilter, $resolver = null): ?string
    {
        $path = $imageOrPath instanceof ImageInterface ? $this->vichHelper->asset($imageOrPath) : $imageOrPath;
        try {
            return $this->filterService->getUrlOfFilteredImage($path, $liipfilter, $resolver);
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

}