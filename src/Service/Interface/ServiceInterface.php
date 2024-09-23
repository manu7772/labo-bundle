<?php
namespace Aequation\LaboBundle\Service\Interface;

use Reflector;

interface ServiceInterface extends Reflector
{

    public function __toString(): string;
    public function getName(): string;

}