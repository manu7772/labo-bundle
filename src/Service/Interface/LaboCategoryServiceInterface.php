<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;

interface LaboCategoryServiceInterface extends AppEntityManagerInterface
{

    public function getCategoryTypeChoices(bool $asHtml = false): array;

}