<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Service\Interface\SiteparamsServiceInterface;
use Aequation\LaboBundle\Entity\Siteparams;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SiteparamsServiceInterface::class, public: true)]
class SiteparamsService extends AppEntityManager implements SiteparamsServiceInterface
{
    public const ENTITY = Siteparams::class;


}