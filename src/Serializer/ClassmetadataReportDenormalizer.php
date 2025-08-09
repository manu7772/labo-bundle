<?php
namespace Aequation\LaboBundle\Serializer;

use Aequation\LaboBundle\Component\ClassmetadataReport;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ClassmetadataReportDenormalizer implements DenormalizerInterface
{

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly DenormalizerInterface $denormalizer,
    ) {}

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $report = $this->denormalizer->denormalize($data, $type, $format, $context);
        return $report;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return
            $type === ClassmetadataReport::class
            && isset($data['classname']);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ClassmetadataReport::class => true,
        ];
    }

}