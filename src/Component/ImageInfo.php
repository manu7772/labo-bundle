<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\ImageServiceInterface;
// Symfony
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImageInfo
{
    public readonly AppServiceInterface $appService;
    protected ImageInterface|string $image;
    // Original image
    public ?string $path = null;
    public array $path_info;
    // Filter
    public array $filtered_path_info;
    public ?string $current_filter = null;
    public array $liipfilters = [];

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
            'original' => [
                'path' => $this->path,
                'path_info' => $this->path_info,
                'current_filter' => $this->current_filter,
                // 'file_path' => $this->getFilePath(),
                // 'base64' => strlen((string) $this->getBase64()).' chars.',
            ],
            // 'liipfilters' => $this->liipfilters,
            'filtered' => [
                'filtered_path_info' => $this->filtered_path_info ?? [],
                // 'file_path' => $this->getFilteredFilePath(),
                // 'base64' => strlen((string) $this->getFilteredBase64()).' chars.',
            ],
        ];
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

    public function getPath(): ?string
    {
        return $this->isValid() ? $this->path : null;
    }

    public function getFilePath(): ?string
    {
        return $this->path_info['corrected_path'] ?? null;
    }

    public function getBase64(): ?string
    {
        if($file_path = $this->getFilePath()) {
            return 'data:image/'.pathinfo($file_path, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($file_path));
        }
        return null;
    }


    /**
     * FILTERED FILE PATH
     */

    public function getCurrentFilter(): ?string
    {
        return $this->current_filter;
    }

    public function addFilter(string $liipfilter): bool
    {
        $this->liipfilters[$liipfilter] = [
            'name' => $liipfilter,
        ];
        if($this->liipfilters[$liipfilter]['available'] = $this->imageService->isAvailableLiipFilter($liipfilter)) {
            $this->liipfilters[$liipfilter]['file_path'] = $this->appService->getDir('public/'.ltrim(preg_replace('#(resolve\/|\.\.\/)#', '', $this->imageService->getLiipCache()->generateUrl($this->path, $liipfilter, [], $this->resolver, UrlGeneratorInterface::RELATIVE_PATH)), DIRECTORY_SEPARATOR));
            $this->liipfilters[$liipfilter]['path_info'] = $this->getImagePathInfo($this->liipfilters[$liipfilter]['file_path']);
            $this->setCurrentFilter($liipfilter);
        } else {
            $this->liipfilters[$liipfilter]['file_path'] = null;
            $this->liipfilters[$liipfilter]['path_info'] = null;
        }
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

    public function getFilteredFilePath(): ?string
    {
        return $this->isValid() ? $this->liipfilters[$this->current_filter]['file_path'] : null;
    }

    public function getFilteredBase64(): ?string
    {
        if($file_path = $this->getFilteredFilePath()) {
            return 'data:image/'.pathinfo($file_path, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($file_path));
        }
        return null;
    }


    /**
     * INTERNAL METHODS
     */

    protected function getImagePathInfo(string $path): array
    {
        $corrected_path = @file_exists($path) ? $path : $this->appService->getDir('public/'.ltrim($path, DIRECTORY_SEPARATOR));
        $path_info = @pathinfo($corrected_path, PATHINFO_ALL);
        if(Encoders::isUrl($corrected_path)) {
            $url_parts = parse_url($corrected_path);
            $path_info = pathinfo($url_parts['path']);
        }
        $path_info['corrected_path'] = $corrected_path;
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

}