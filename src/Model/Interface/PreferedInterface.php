<?php
namespace Aequation\LaboBundle\Model\Interface;

interface PreferedInterface extends AppEntityInterface
{
    public function isPrefered(): bool;
    public function setPrefered(bool $prefered): static;
}

