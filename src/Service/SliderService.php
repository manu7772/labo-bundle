<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\Overlay;
use Aequation\LaboBundle\Entity\LaboSlider;
use Aequation\LaboBundle\Model\Attribute\CssClasses;
// Symfony
use Aequation\LaboBundle\Service\EcollectionService;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Aequation\LaboBundle\Service\Interface\SliderServiceInterface;

#[AsAlias(SliderServiceInterface::class, public: true)]
class SliderService extends EcollectionService implements SliderServiceInterface
{
    public const ENTITY = LaboSlider::class;

    #[CssClasses(target: 'value')]
    public static function declareCss(): array
    {
        $css = array_merge($css ?? [], Overlay::declareCss());
        return array_unique(array_values($css));
    }


}