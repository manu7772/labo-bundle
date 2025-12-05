<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Model\Interface\ScreenableInterface;
use Aequation\LaboBundle\Service\Interface\LaboWebpageServiceInterface;

abstract class LaboWebpageService extends EcollectionService implements LaboWebpageServiceInterface
{

    public function getWebpageChoices(?ScreenableInterface $screenable = null): array
    {
        $choices = [];
        $webpages = $this->getRepository()->findBy([], ['name' => 'ASC']);
        foreach ($webpages as $webpage) {
            $choices[$webpage->getName()] = $webpage->getId();
        }
        return $choices;
    }

}