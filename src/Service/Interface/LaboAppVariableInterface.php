<?php
namespace Aequation\LaboBundle\Service\Interface;

interface LaboAppVariableInterface
{

    public function getHost(): ?string;
    public function isLocalHost(): bool;
    public function isProdHost(?array $countries = null): bool;

}