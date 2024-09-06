<?php
namespace Aequation\LaboBundle\Repository;

use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Repository\Base\CommonRepos;
use Aequation\LaboBundle\Repository\Interface\PdfRepositoryInterface;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<Pdf>
 */
#[AsAlias(PdfRepositoryInterface::class, public: true)]
class PdfRepository extends CommonRepos implements PdfRepositoryInterface
{

    const ENTITY_CLASS = Pdf::class;
    const NAME = 'pdf';

}
