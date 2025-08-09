<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface ImageServiceInterface extends ItemServiceInterface
{

    public function getBrowserPath(ImageInterface $image, ?string $filter = null, array $runtimeConfig = [], $resolver = null, $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string;

}