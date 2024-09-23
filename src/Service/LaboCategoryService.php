<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Model\Interface\LaboCategoryInterface;
use Aequation\LaboBundle\Service\Interface\LaboCategoryServiceInterface;
use Aequation\LaboBundle\Service\AppEntityManager;
use ReflectionClass;

abstract class LaboCategoryService extends AppEntityManager implements LaboCategoryServiceInterface
{

    public function getCategoryTypeChoices(
        bool $asHtml = false,
        bool $allnamespaces = false,
        bool $onlyInstantiables = true
    ): array
    {
        $list = $this->getEntityClassesOfInterface(LaboCategoryInterface::class, false, $onlyInstantiables);
        $class = reset($list);
        if(!empty($class)) {
            $relateds = $this->getRelateds(static::ENTITY ?? $class, null, false);
            $entities = $asHtml
                ? $this->getEntityNamesChoices(true, true, $allnamespaces, $onlyInstantiables)
                : $this->getEntityNames(false, $allnamespaces, $onlyInstantiables);
            $list = array_filter(
                $entities,
                function($class) use ($relateds) {
                    // return !is_a($class, static::class, true);
                    return array_key_exists($class, $relateds);
                }
            );
        }
        return $list;
    }

}