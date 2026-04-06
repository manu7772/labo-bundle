<?php
namespace Aequation\LaboBundle\Component\Interface;

use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
// PHP
use Stringable;

interface WebpageContainerInterface extends Stringable
{
    public function getId(): string;
    public static function getShortname(): string;
    public function getName(): string;
    public function isWpContainer(): bool;
    public function getElements(): array;
    public function getUserItems(): array;
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