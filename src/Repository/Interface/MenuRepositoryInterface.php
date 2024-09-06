<?php
namespace Aequation\LaboBundle\Repository\Interface;

use Aequation\LaboBundle\Model\Interface\MenuInterface;

interface MenuRepositoryInterface extends EcollectionRepositoryInterface
{

    public function findPreferedMenu(): ?MenuInterface;

}