<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\Portrait;
use Aequation\LaboBundle\Repository\ImageRepository;
use Aequation\LaboBundle\Repository\Interface\PortraitRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ImageRepository<Image>
 *
 * @method Portrait|null find($id, $lockMode = null, $lockVersion = null)
 * @method Portrait|null findOneBy(array $criteria, array $orderBy = null)
 * @method Portrait[]    findAll()
 * @method Portrait[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[AsAlias(PortraitRepositoryInterface::class, public: true)]
class PortraitRepository extends ImageRepository implements PortraitRepositoryInterface
{
    const ENTITY_CLASS = Portrait::class;
    const NAME = 'portrait';

}