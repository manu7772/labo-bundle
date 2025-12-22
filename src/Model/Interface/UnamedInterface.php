<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Entity\Uname;

interface UnamedInterface
{

    public function autoUpdateUname(): static;
    public function updateUname(?string $uname = null): static;
    public function getUname(): ?Uname;

}