<?php
namespace Aequation\LaboBundle\Service\Interface;

interface CrudvoterServiceInterface extends AppEntityServiceInterface
{

    public function getFirewallChoices(bool $onlyMains = true): array;

}