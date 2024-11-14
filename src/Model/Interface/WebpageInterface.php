<?php
namespace Aequation\LaboBundle\Model\Interface;

use App\Entity\Menu;

interface WebpageInterface extends ItemInterface, CreatedInterface, EnabledInterface, SlugInterface, PreferedInterface
{

    public function getMainmenu(): ?MenuInterface;
    public function getTwigfile(): ?string;

}

