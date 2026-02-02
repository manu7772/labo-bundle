<?php
namespace Aequation\LaboBundle\Twig\Components;

use Aequation\LaboBundle\Model\Interface\SliderInterface;
use Doctrine\Common\Collections\Collection;
// Symfony
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
class PdfListComponent
{
    public Collection $pdfiles;
}