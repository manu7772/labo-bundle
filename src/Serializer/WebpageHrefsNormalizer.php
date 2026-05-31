<?php
namespace Aequation\LaboBundle\Serializer;

use Aequation\LaboBundle\Model\Interface\ScreenableInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
// Symfony
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;


class WebpageHrefsNormalizer implements NormalizerInterface
{

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
        protected RouterInterface $router,
    ) {}

    public function normalize($webpageable, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($webpageable, $format, $context);
        if(in_array('hrefs', $context['groups'])) {
            // Main href
            $data['hrefs']['absolute_url'] = $this->getWebpageUrl($webpageable->getSlug(), UrlGeneratorInterface::ABSOLUTE_URL);
            // Default href
            $data['href'] = $data['hrefs']['absolute_url'];
            // Others hrefs
            $data['hrefs']['absolute_path'] = $this->getWebpageUrl($webpageable->getSlug(), UrlGeneratorInterface::ABSOLUTE_PATH);
            $data['hrefs']['relative_url'] = $this->getWebpageUrl($webpageable->getSlug(), UrlGeneratorInterface::RELATIVE_PATH);
        }
        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return ($data instanceof ScreenableInterface || $data instanceof WebpageInterface) && in_array('hrefs', (array)($context['groups'] ?? []));
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ScreenableInterface::class => true,
            WebpageInterface::class => true
        ];
    }

    /**
     * Get URL of a webpage (only if it can be generated)
     * 
     * @param null|string|array $elements
     * @return ?string
     */
    public function getWebpageUrl(
        null|string|array $elements,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): ?string
    {
        $parameters = [];
        foreach((array)$elements as $element) {
            switch (true) {
                case is_string($element):
                    $parameters[] = $element;
                    break;
                case $element instanceof SlugInterface:
                    $parameters[] = $element->getSlug();
                    break;
            }
        }
        try {
            return $this->router->generate(name: 'app_webpage', parameters: ['path' => implode('/', $parameters)], referenceType: $referenceType);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return null;
    }


}