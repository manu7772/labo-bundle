<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;

interface FinalPhonelinkInterface extends LaboRelinkInterface
{
    public function setPhone(string $phone): static;
    public function getPhone(): string;
}