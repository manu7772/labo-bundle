<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\LaboCategoryInterface;

interface FinalCategoryInterface extends LaboCategoryInterface
{
    public static function getIdForMainEntreprise(): ?int; // use static::ID_OF_MAIN_FOR_ENTREPRISE
}