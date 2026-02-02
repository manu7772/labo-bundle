<?php
namespace Aequation\LaboBundle\Component\Interface;


interface ImageInfoInterface
{
    public function getData(): array;
    public function __call(string $name, array $arguments);
    public function isValid(): bool;
    public function getAvailableFilters(): array;
    public function getCurrentFilter(): ?string;
    public function setCurrentFilter(string $liipfilter): bool;
    public function hasFilter(string $liipfilter, bool $onlyAvailable = true): bool;
    public function checkAllFilters(): array;
    public static function estimateRatio(?int $x, ?int $y): string;
}