<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Repository\ImageRepository;
use Aequation\LaboBundle\Entity\Picture;
use Aequation\LaboBundle\Repository\Interface\PictureRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ImageRepository<Picture>
 *
 * @method Picture|null find($id, $lockMode = null, $lockVersion = null)
 * @method Picture|null findOneBy(array $criteria, array $orderBy = null)
 * @method Picture[]    findAll()
 * @method Picture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[AsAlias(PictureRepositoryInterface::class, public: true)]
class PictureRepository extends ImageRepository implements PictureRepositoryInterface
{

    const ENTITY_CLASS = Picture::class;
    const NAME = 'picture';

}
