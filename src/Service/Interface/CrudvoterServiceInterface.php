<?php
namespace Aequation\LaboBundle\Service\Interface;

interface CrudvoterServiceInterface extends AppEntityManagerInterface
{

    public function getFirewallChoices(bool $onlyMains = true): array;

}