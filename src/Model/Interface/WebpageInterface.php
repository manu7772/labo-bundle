<?php
namespace Aequation\LaboBundle\Model\Interface;

use Doctrine\Common\Collections\Collection;

interface WebpageInterface extends EcollectionInterface, CreatedInterface, EnabledInterface, SlugInterface, PreferedInterface
{
    public function getMainmenu(): ?MenuInterface;
    public function getTwigfile(): ?string;
    public function removeWebsection(WebsectionInterface $section): static;
    public function getWebsectionsOrdered(bool $filter_active = false): Collection;
    public function getWebsections(?string $sectiontype = null, bool $filter_active = false): Collection;
}

