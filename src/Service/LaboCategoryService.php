<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Model\Interface\LaboCategoryInterface;
use Aequation\LaboBundle\Service\Interface\LaboCategoryServiceInterface;
use Aequation\LaboBundle\Service\AppEntityManager;

abstract class LaboCategoryService extends AppEntityManager implements LaboCategoryServiceInterface
{

    public function getCategoryTypeChoices(
        bool $asHtml = false
    ): array
    {
        $list = $this->getEntityClassesOfInterface(LaboCategoryInterface::class, false);
        $relateds = $this->getRelateds(static::ENTITY ?? reset($list), null, false);
        $entities = $asHtml
            ? $this->getEntityNamesChoices(true, true, false, true)
            : $this->getEntityNames(false, false, false);
        return array_filter(
            $entities,
            function($class) use ($relateds) {
                // return !is_a($class, static::class, true);
                return array_key_exists($class, $relateds);
            }
        );
    }

}