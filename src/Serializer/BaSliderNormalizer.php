<?php
namespace Aequation\LaboBundle\Serializer;

use Aequation\LaboBundle\Model\Interface\ImageInterface;
use App\Entity\Slide;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class BaSliderNormalizer implements NormalizerInterface
{

    // public const THUMBNAIL_LIIP_FILTER = 'tiny_q'; // miniature_q

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache,
        // private ImageServiceInterface $imageService,
        // private UrlGeneratorInterface $router,
        // private AdminUrlGenerator $adminRouter,
    ) {}

    public function normalize($slide, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($slide, $format, $context);
        /** @var Slide $slide */
        if(in_array('BaSlider', $context['groups'])) {
            $baSliderImages = [];
            $baSliderImages[] = [
                'image_path' => $this->getBrowserPath($slide, $slide->getLiipFilterByTempParent()),
                'thumb_path' => $this->getBrowserPath($slide, Slide::THUMBNAIL_LIIP_FILTER),
            ];
            foreach ($slide->getSlidebases() as $slidebase) {
                $baSliderImages[] = [
                    'image_path' => $this->getBrowserPath($slidebase, $slide->getLiipFilterByTempParent()),
                    'thumb_path' => $this->getBrowserPath($slidebase, Slide::THUMBNAIL_LIIP_FILTER),
                ];
            }
            $data['baSliderImages'] = $baSliderImages;
            $data['liip_filter_by_temp_parent'] = $slide->getLiipFilterByTempParent();
            // dd($this, $data, $context);
        }

        // Here, add, edit, or delete some data:
        // $data['href']['self'] = $this->router->generate('image_show', [
        //     'id' => $slide->getId(),
        // ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        // $data['admin'] = [
        //     'home' => $this->adminRouter->setRoute('admin_home')->generateUrl(),
        // ];
        
        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Slide;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Slide::class => true,
        ];
    }

    public function getBrowserPath(
        ImageInterface $image,
        string $filter = null,
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