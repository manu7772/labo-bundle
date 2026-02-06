<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\LaboSlider;
use Aequation\LaboBundle\Repository\EcollectionRepository;
use Aequation\LaboBundle\Repository\Interface\SliderRepositoryInterface;

/**
 * @extends EcollectionRepository<LaboSlider>
 *
 * @method LaboSlider|null find($id, $lockMode = null, $lockVersion = null)
 * @method LaboSlider|null findOneBy(array $criteria, array $orderBy = null)
 * @method LaboSlider[]    findAll()
 * @method LaboSlider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class LaboSliderRepository extends EcollectionRepository implements SliderRepositoryInterface
{
    const ENTITY_CLASS = LaboSlider::class;
    const NAME = 'laboslider';

}