<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\ImageInfoInterface;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\ImageServiceInterface;
use Aequation\LaboBundle\Service\Tools\Strings;
// Symfony
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
// PHP
use BadMethodCallException;

class ImageInfo implements ImageInfoInterface
{
    public const RATIO_LIMIT = 1.5;
    public const WIDTH_LIMIT = 800;
    public const IMG_FORMAT_LANDSCAPE = 'landscape';
    public const IMG_FORMAT_PORTRAIT = 'portrait';
    public const IMG_FORMAT_SQUARE = 'square';
    public const IMG_FORMAT_UNKNOWN = 'unknown';

    public readonly AppServiceInterface $appService;
    protected ImageInterface|string $image;
    // Original image
    public ?string $path = null;
    public array $path_info;
    // Filter
    public ?string $current_filter = null;
    public array $liipfilters = [];
    private int $cpt = 0;

    public function __construct(
        protected ImageServiceInterface $imageService,
        ImageInterface|string $image,
        null|string|false $liipfilter = null,
        protected $resolver = null
    ) {
        $this->appService = $imageService->getAppService();
        $this->initialize($image, $liipfilter);
    }

    public function getData(): array
    {
        return [
            'valid' => $this->isValid(),
            // 'image' => $this->image,
            'current_filter' => $this->getCurrentFilter(),
            'original' => array_merge([
                // 'path' => $this->getPath(),
                // 'file_path' => $this->getFilePath(),
                'base64' => strlen((string) $this->getBase64()).' chars.',
                // 'orientation' => $this->getOrientation(),
            ], $this->path_info),
            'filtered' => array_merge([
                // 'current_filter' => $this->getCurrentFilter(),
                // 'file_path' => $this->getFilteredFilePath(),
                'base64' => strlen((string) $this->getFilteredBase64()).' chars.',
                // 'orientation' => $this->getFilteredOrientation(),
            ], $this->getFilteredPathInfo()),
            'liipfilters' => $this->liipfilters,
        ];
    }

    public function __call(string $name, array $arguments) {
        $name_snake = Strings::stringFormated(preg_replace('#^get#', '', $name), 'snake');
        $name_snake_tronqued = preg_replace('#^filtered_#', '', $name_snake);
        if($this->cpt++ > 100) {
            throw new BadMethodCallException(sprintf('Possible infinite loop detected in %s::__call for method "%s" (snaked: %s).', __CLASS__, $name, $name_snake));
        }
        dump('ImageInfo::__call '.$name.' ('.$name_snake.' / [filtered_]'.$name_snake_tronqued.')');
        switch (true) {
            // Original info
            case $name_snake === 'path_info':
                return $this->path_info;
                break;
            case array_key_exists($name_snake, $this->path_info):
                return $this->path_info[$name_snake];
                break;
            case $name_snake === 'base64':
                return $this->path_info['file_exists'] && ($file_path = $this->path_info['corrected_path'])
                    ? 'data:image/'.pathinfo($file_path, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($file_path))
                    : null;
                break;
            // Filtered info
            case $name_snake === 'filtered_path_info':
                return $this->isValid() ? $this->liipfilters[$this->current_filter] ?? [] : [];
                break;
            case !empty($fpi = $this->getFilteredPathInfo()) && array_key_exists($name_snake_tronqued, $fpi):
                return $fpi[$name_snake_tronqued];
                break;
            case $name_snake === 'filtered_base64':
                return $this->filteredFileExists() && ($file_path = $this->getFilteredFilePath())
                    ? 'data:image/'.pathinfo($file_path, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($file_path))
                    : null;
                break;
            case $name_snake === 'filtered_valid':
                $filtered = $this->isValid() ? $this->liipfilters[$this->current_filter] ?? [] : [];
                return !empty($filtered) && ($filtered['available'] ?? false) && ($filtered['file_exists'] ?? false);
                break;
            default:
                // not supported
                throw new BadMethodCallException(sprintf('Undefined method "%s" called in %s.', $name, __CLASS__));
                // return null;
                break;
        }
    }

    protected function initialize(ImageInterface|string $image, null|string|false $liipfilter = null): void
    {
        $this->image = $image;
        $this->path = $this->imageService->getVichHelper()->asset($image);
        $this->path_info = $this->getImagePathInfo($this->path);
        // Filter
        switch (true) {
            case $liipfilter === false:
                // No filter
                break;
            case empty(trim((string) $liipfilter)):
                if($liipfilter = $image instanceof ImageInterface ? $image->getImagefilter() : null) {
                    $this->addFilter($liipfilter);
                }
                break;
            default:
                $this->addFilter($liipfilter);
                break;
        }
    }

    public function isValid(): bool
    {
        return $this->path_info['file_exists'] && !empty($this->getCurrentFilter());
    }


    /**
     * ORIGINAL FILE PATH
     */


    /**
     * FILTERED FILE PATH
     */

    public function getAvailableFilters(): array
    {
        return $this->imageService->getLiipFiltersNames();
    }

    public function getCurrentFilter(): ?string
    {
        return $this->current_filter;
    }

    protected function addFilter(string $liipfilter): bool
    {
        $this->liipfilters[$liipfilter] = [
            'name' => $liipfilter,
        ];
        if($this->liipfilters[$liipfilter]['available'] = $this->imageService->isAvailableLiipFilter($liipfilter)) {
            $this->liipfilters[$liipfilter]['file_path'] = $this->appService->getDir('public/'.ltrim(preg_replace('#(resolve\/|\.\.\/)#', '', $this->imageService->getLiipCache()->generateUrl($this->path, $liipfilter, [], $this->resolver, UrlGeneratorInterface::RELATIVE_PATH)), DIRECTORY_SEPARATOR));
            // $this->liipfilters[$liipfilter]['path_info'] = $this->getImagePathInfo($this->liipfilters[$liipfilter]['file_path']);
            $this->liipfilters[$liipfilter] = array_merge($this->liipfilters[$liipfilter], $this->getImagePathInfo($this->liipfilters[$liipfilter]['file_path'], $liipfilter));
            if($this->liipfilters[$liipfilter]['file_exists']) {
                $imgsize = @getimagesize($this->liipfilters[$liipfilter]['file_path']);
                if($imgsize) {
                    $this->liipfilters[$liipfilter]['width'] = $imgsize[0];
                    $this->liipfilters[$liipfilter]['height'] = $imgsize[1];
                }
            }
            $this->liipfilters[$liipfilter]['liipfilter'] = $this->imageService->getLiipFilter($liipfilter);
            $this->setCurrentFilter($liipfilter);
        } else {
            $this->liipfilters[$liipfilter]['file_path'] = null;
            // $this->liipfilters[$liipfilter]['path_info'] = null;
        }
        $this->liipfilters[$liipfilter]['valid'] = $this->liipfilters[$liipfilter]['available'] && $this->liipfilters[$liipfilter]['file_exists'];
        return $this->liipfilters[$liipfilter]['available'];
    }

    public function setCurrentFilter(string $liipfilter): bool
    {
        if(!isset($this->liipfilters[$liipfilter])) {
            $this->addFilter($liipfilter);
        }
        if(!$this->liipfilters[$liipfilter]['available']) {
            return false;
        }
        $this->current_filter = $liipfilter;
        return true;
    }

    public function hasFilter(string $liipfilter, bool $onlyAvailable = true): bool
    {
        return isset($this->liipfilters[$liipfilter]) && (!$onlyAvailable || $this->liipfilters[$liipfilter]['available']);
    }

    public function checkAllFilters(): array
    {
        $results = [];
        foreach($this->getAvailableFilters() as $filter_name) {
            if($this->addFilter($filter_name)) {
                $results[$filter_name] = $this->liipfilters[$filter_name]['valid'];
            }
            // $results[$filter_name] = $this->addFilter($filter_name);
        }
        return $results;
    }


    /**
     * INTERNAL METHODS
     */

    protected function getImagePathInfo(string $path, ?string $liipfilter = null): array
    {
        $corrected_path = @file_exists($path) ? $path : $this->appService->getDir('public/'.ltrim($path, DIRECTORY_SEPARATOR));
        $path_info = @pathinfo($corrected_path, PATHINFO_ALL);
        if(Encoders::isUrl($corrected_path)) {
            $url_parts = parse_url($corrected_path);
            $path_info = pathinfo($url_parts['path']);
        }
        $path_info['path'] = $path;
        $path_info['corrected_path'] = $corrected_path;
        $path_info['file_exists'] = @file_exists($corrected_path);
        // If is a filtered file
        if(!empty($liipfilter ?? null) && preg_match('#\/cache\/'.$liipfilter.'#', $corrected_path)) {
            if(!$path_info['file_exists']) {
                // Filtered does not exists, create it!
                $this->imageService->generateFilteredImage($this->path, $liipfilter, $this->resolver);
                return $this->getImagePathInfo($path, null); // Re-call without filter to get updated info
            }
        }
        $path_info['created_at'] = null;
        $path_info['modified_at'] = null;
        $path_info['width'] = null;
        $path_info['height'] = null;
        if($path_info['file_exists']) {
            $creation_date = filectime($corrected_path);
            $path_info['created_at'] = date("F d Y H:i:s", $creation_date);
            $modification_date = filemtime($corrected_path);
            $path_info['modified_at'] = date("F d Y H:i:s", $modification_date);
            $path_info['size'] = @filesize($corrected_path);
            $img_size = @getimagesize($corrected_path, /* $image_info */);
            // dump($img_size);
            if($img_size) {
                $path_info['mime'] = $img_size['mime'] ?? null;
                $path_info['width'] = $img_size[0];
                $path_info['height'] = $img_size[1];
                $path_info['ratio'] = $img_size[0] / $img_size[1];
                $path_info['orientation'] = static::estimateRatio($img_size[0], $img_size[1]);
            }
        }
        return $path_info;
    }

    public static function estimateRatio(?int $x, ?int $y): string
    {
        if(is_null($x) || is_null($y)) {
            return self::IMG_FORMAT_UNKNOWN;
        }
        if($x > $y * static::RATIO_LIMIT && $x >= static::WIDTH_LIMIT) {
            return self::IMG_FORMAT_LANDSCAPE;
        }
        if($y > $x * static::RATIO_LIMIT) {
            return self::IMG_FORMAT_PORTRAIT;
        }
        return self::IMG_FORMAT_SQUARE;
    }

}