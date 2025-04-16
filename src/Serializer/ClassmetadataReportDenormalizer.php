<?php
namespace Aequation\LaboBundle\Serializer;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ClassmetadataReportDenormalizer implements DenormalizerInterface
{

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly DenormalizerInterface $denormalizer,
        // private readonly AppEntityManagerInterface $appEntityManager
    ) {}

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // $data['appEntityManager'] = $this->appEntityManager;
        $report = $this->denormalizer->denormalize($data, $type, $format, $context);
        // $report = new ClassmetadataReport($this->appEntityManager, $data['classname']);
        // dump($report);
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