<?php
namespace Aequation\LaboBundle\Service\Interface;

interface LaboCategoryServiceInterface extends AppEntityServiceInterface
{

    public function getCategoryTypeChoices(bool $asHtml = false): array;

}