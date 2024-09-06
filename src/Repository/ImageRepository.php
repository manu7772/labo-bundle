<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Repository\ItemRepository;
use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Repository\Interface\ImageRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ItemRepository<Image>
 *
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[AsAlias(ImageRepositoryInterface::class, public: true)]
class ImageRepository extends ItemRepository implements ImageRepositoryInterface
{

    const ENTITY_CLASS = Image::class;
    const NAME = 'image';

}
