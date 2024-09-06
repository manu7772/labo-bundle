<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Service\Interface\UnameServiceInterface;
use Aequation\LaboBundle\Service\AppEntityManager;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UnameServiceInterface::class, public: true)]
class UnameService extends AppEntityManager implements UnameServiceInterface
{

}