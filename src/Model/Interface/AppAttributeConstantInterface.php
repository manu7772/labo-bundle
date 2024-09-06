<?php
namespace Aequation\LaboBundle\Model\Interface;

use ReflectionClassConstant;

interface AppAttributeConstantInterface extends AppAttributeInterface
{

    public function setConstant(ReflectionClassConstant $constant): static;
    public function getValue(): mixed;

}
