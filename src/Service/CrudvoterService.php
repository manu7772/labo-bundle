<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Crudvoter;
use Aequation\LaboBundle\Service\Interface\CrudvoterServiceInterface;
use Aequation\LaboBundle\Service\AppEntityManager;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CrudvoterServiceInterface::class, public: true)]
class CrudvoterService extends AppEntityManager implements CrudvoterServiceInterface
{

    public const ENTITY = Crudvoter::class;

    public function getFirewallChoices(bool $onlyMains = true): array
    {
        return $this->appService->getFirewallChoices($onlyMains);
    }


}