<?php
namespace Aequation\LaboBundle\Model\Interface;

use Doctrine\Common\Collections\Collection;

interface WebpageInterface extends ItemInterface, CreatedInterface, EnabledInterface, SlugInterface, PreferedInterface
{
    public function getMainmenu(): ?MenuInterface;
    public function getTwigfile(): ?string;
    public function removeWebsection(WebsectionInterface $section): static;
    public function getWebsectionsOrdered(bool $filter_active = false): Collection;
}

