<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

interface ImageServiceInterface extends ItemServiceInterface
{
    public static function estimateRatio(int $x, int $y): string;
    public function getImageInfo(?ImageInterface $image, null|string|false $liipfilter = null, $resolver = null, bool $generate = true): array;
    // public function store(ImageInterface $image, string $liipfilter): void;
    public function getBrowserPath(ImageInterface $image, ?string $filter = null, array $runtimeConfig = [], $resolver = null, $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string;
    public function getLiipFilters(): FilterConfiguration;

}