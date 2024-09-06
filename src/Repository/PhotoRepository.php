<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Repository\ImageRepository;
use Aequation\LaboBundle\Entity\Photo;
use Aequation\LaboBundle\Repository\Interface\PhotoRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ImageRepository<Photo>
 *
 * @method Photo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Photo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Photo[]    findAll()
 * @method Photo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[AsAlias(PhotoRepositoryInterface::class, public: true)]
class PhotoRepository extends ImageRepository implements PhotoRepositoryInterface
{

    const ENTITY_CLASS = Photo::class;
    const NAME = 'photo';

}
