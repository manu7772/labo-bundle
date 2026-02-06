<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\LaboSlidebase;
use Aequation\LaboBundle\Repository\ImageRepository;
use Aequation\LaboBundle\Repository\Interface\SlidebaseRepositoryInterface;

/**
 * @extends ImageRepository<Image>
 *
 * @method LaboSlidebase|null find($id, $lockMode = null, $lockVersion = null)
 * @method LaboSlidebase|null findOneBy(array $criteria, array $orderBy = null)
 * @method LaboSlidebase[]    findAll()
 * @method LaboSlidebase[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class LaboSlidebaseRepository extends ImageRepository implements SlidebaseRepositoryInterface
{
    const ENTITY_CLASS = LaboSlidebase::class;
    const NAME = 'laboslidebase';

}