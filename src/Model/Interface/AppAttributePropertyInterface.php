<?php
namespace Aequation\LaboBundle\Model\Interface;

use ReflectionProperty;

interface AppAttributePropertyInterface extends AppAttributeInterface
{

    public function setProperty(ReflectionProperty $property): static;

}