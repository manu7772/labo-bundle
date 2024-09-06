<?php
namespace Aequation\LaboBundle\Model\Interface;

use Serializable;

interface CssClassInterface extends Serializable
{

    public function getCssClasses(): array;

}