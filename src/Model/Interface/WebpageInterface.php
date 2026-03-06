<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Model\Final\FinalWebsectionInterface;
use Doctrine\Common\Collections\Collection;

interface WebpageInterface extends EcollectionInterface, CreatedInterface, EnabledInterface, SlugInterface, PreferedInterface
{
    public function getMainmenu(): ?MenuInterface;
    public function getTwigfile(): ?string;
    public function removeWebsection(FinalWebsectionInterface $section): static;
    public function getFirstWebsection(string $sectiontype, bool $filter_active = false): ?FinalWebsectionInterface;
    public function getWebsectionsOrdered(bool $filter_active = false): Collection;
    public function getWebsections(?string $sectiontype = null, bool $filter_active = false): Collection;
}

