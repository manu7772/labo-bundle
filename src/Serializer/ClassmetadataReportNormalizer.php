<?php
namespace Aequation\LaboBundle\Serializer;

use Aequation\LaboBundle\Component\ClassmetadataReport;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ClassmetadataReportNormalizer implements NormalizerInterface
{

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer
    ) {}

    public function normalize($classmetadataReport, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($classmetadataReport, $format, $context);
        dump($data);
        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ClassmetadataReport;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ClassmetadataReport::class => true,
        ];
    }

}