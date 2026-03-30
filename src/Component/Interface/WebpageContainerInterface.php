<?php
namespace Aequation\LaboBundle\Component\Interface;

use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;

interface WebpageContainerInterface
{
    public function getAppEntityManager(): AppEntityManagerInterface;
    public function toArray(): array;
    public function getWebpage(): ?FinalWebpageInterface;
    public function getTwigfile(): string;
    public function getDirection(): string;
    public function isHome(): bool;
    public function isWebpage(): bool;
    public function isMenu(): bool;
    public function isError(): bool;
}