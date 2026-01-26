<?php
namespace Aequation\LaboBundle\Twig\Components;

use Aequation\LaboBundle\Model\Interface\SliderInterface;
// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
class SimpleCarouselComponent
{
    public SliderInterface $slider;
    public bool $controls = true;
    public bool $indicators = true;
}