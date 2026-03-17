<?php
namespace Aequation\LaboBundle\Twig\Components;

use Aequation\LaboBundle\Model\Interface\SliderInterface;
// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
class ImagesSquareSlider
{
    public ?SliderInterface $slider;
}