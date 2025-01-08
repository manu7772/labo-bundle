<?php
namespace Aequation\LaboBundle\Model\Interface;

interface WebpageInterface extends ItemInterface, CreatedInterface, EnabledInterface, SlugInterface, PreferedInterface
{
    public function getMainmenu(): ?MenuInterface;
    public function getTwigfile(): ?string;
    public function removeWebsection(WebsectionInterface $section): static;
}

