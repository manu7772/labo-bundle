<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\LaboSlide;
use Aequation\LaboBundle\Repository\ImageRepository;
use Aequation\LaboBundle\Repository\Interface\SlideRepositoryInterface;

/**
 * @extends ImageRepository<Image>
 *
 * @method LaboSlide|null find($id, $lockMode = null, $lockVersion = null)
 * @method LaboSlide|null findOneBy(array $criteria, array $orderBy = null)
 * @method LaboSlide[]    findAll()
 * @method LaboSlide[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class LaboSlideRepository extends ImageRepository implements SlideRepositoryInterface
{
    const ENTITY_CLASS = LaboSlide::class;
    const NAME = 'laboslide';

}