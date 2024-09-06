<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Aequation\LaboBundle\Service\Interface\PdfServiceInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;

use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[AsAlias(PdfServiceInterface::class, public: true)]
class PdfService extends ItemService implements PdfServiceInterface
{
    public const ENTITY = Pdf::class;

    public function __construct(
        protected EntityManagerInterface $em,
        protected AppServiceInterface $appService,
        protected AccessDecisionManagerInterface $accessDecisionManager,
        protected ValidatorInterface $validator,
        protected UploaderHelper $vichHelper,
        protected CacheManager $liipCache,
    )
    {
        parent::__construct($em, $appService, $accessDecisionManager, $validator);
    }

    public function getBrowserPath(
        PdfInterface $pdf,
    ): string
    {
        $browserPath = $this->vichHelper->asset($pdf);
        // dump($browserPath);
        return $browserPath;
    }

}