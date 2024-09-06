<?php
namespace Aequation\LaboBundle\Security\CrudvoterAttribute\Base;

use Aequation\LaboBundle\Security\CrudvoterAttribute\Interface\CrudvoterInterface;

use Exception;
use ReflectionAttribute;

abstract class BaseCrudvoter implements CrudvoterInterface
{

    public function __construct(
        ReflectionAttribute $attr
    )
    {
        foreach ($attr->getArguments() as $name => $value) {
            if(!isset($this->$name)) throw new Exception(vsprintf('This attribute "%s" does not exists!', [$name]));
            $this->$name = $value;
        }
    }


}