<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;

interface FinalEmailinkInterface extends LaboRelinkInterface
{
    public function setEmail(string $email): static;
    public function getEmail(): string;
}