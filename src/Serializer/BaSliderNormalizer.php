<?php
namespace Aequation\LaboBundle\Serializer;

use Aequation\LaboBundle\Entity\LaboSlide;
use Aequation\LaboBundle\Entity\LaboSlidebase;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class BaSliderNormalizer implements NormalizerInterface
{
    public const RESPECT_FORMAT = 'normal_h500';

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache,
    ) {}

    public function normalize($slide, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($slide, $format, $context);
        /** @var LaboSlide $slide */
        if(in_array('BaSlider', $context['groups'])) {
            $baSliderImages = [];
            // Add slidebases images if the slide type allows it
            foreach ($slide::SLIDE_TYPES as $name => $values) {
                $max_slidebases = $values['max_slidebases'] ?? 0;
                if($slide->getSlidetype() === $name && $max_slidebases > 0) {
                    foreach ($slide->getSlidebases()->slice(0, $max_slidebases) as $slidebase) {
                        /** @var LaboSlidebase $slidebase */
                        $baSliderImages[] = [
                            'respect_format_path' => $this->getBrowserPath($slidebase, static::RESPECT_FORMAT),
                            'image_filter_path' => $this->getBrowserPath($slidebase, $slide->getImagefilter()),
                            'parent_filter_path' => $this->getBrowserPath($slidebase, $slide->getLiipFilterByTempParent()),
                            'thumb_path' => $this->getBrowserPath($slidebase, $slide->getThumbnailLiipFilter()),
                            'content' => $slidebase->getContent(),
                        ];
                    }
                }
            }
            $baSliderImages[] = [
                'respect_format_path' => $this->getBrowserPath($slide, static::RESPECT_FORMAT),
                'image_filter_path' => $this->getBrowserPath($slide, $slide->getImagefilter()),
                'parent_filter_path' => $this->getBrowserPath($slide, $slide->getLiipFilterByTempParent()),
                'thumb_path' => $this->getBrowserPath($slide, $slide->getThumbnailLiipFilter()),
                'content' => $slide->getContent(),
            ];
            $data['baSliderImages'] = $baSliderImages;
            $data['liip_filter_by_temp_parent'] = $slide->getLiipFilterByTempParent();
        }
        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LaboSlide;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            LaboSlide::class => true,
        ];
    }

    public function getBrowserPath(
        ImageInterface $image,
        ?string $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): string
    {
        $browserPath = $this->vichHelper->asset($image);
        if($filter) {
            $browserPath = $this->liipCache->getBrowserPath($browserPath, $filter, $runtimeConfig, $resolver, $referenceType);
        }
        return $browserPath;
    }

}