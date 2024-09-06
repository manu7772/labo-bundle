<?php
namespace Aequation\LaboBundle\Model\Interface;

use ReflectionMethod;

interface AppAttributeMethodInterface extends AppAttributeInterface
{

    public function setMethod(ReflectionMethod $method): static;

}
