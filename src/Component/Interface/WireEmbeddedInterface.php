<?php
namespace Aequation\LaboBundle\Component\Interface;

use Stringable;

interface WireEmbeddedInterface extends Stringable
{
    public function toArray(): array;
    public function isEmpty(): bool;
}