<?php
namespace Aequation\LaboBundle\Model\Interface;

use Doctrine\Common\Collections\Collection;

interface SliderInterface extends EcollectionInterface
{

    public function getSlides(): Collection;
    public static function getSlidertypeChoices(bool $asHtml = true): array;
    
}

