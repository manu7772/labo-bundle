<?php
namespace Aequation\LaboBundle\Model\Attribute;

use Aequation\LaboBundle\Model\Interface\AppAttributeClassInterface;
use Aequation\LaboBundle\Model\Interface\AppAttributePropertyInterface;
use Attribute;
use ReflectionClass;
use ReflectionProperty;

/**
 * Entity has RelationOrder Attribute
 * @author emmanuel:dujardin Aequation
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HasRelationOrder extends baseClassAttribute implements AppAttributeClassInterface
{

    // public function __construct() {
    // }

}