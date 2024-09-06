<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\ImageServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[AsAlias(ImageServiceInterface::class, public: true)]
class ImageService extends ItemService implements ImageServiceInterface
{
    public const ENTITY = Image::class;

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
        ImageInterface $image,
        string $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): string
    {
        $browserPath = $this->vichHelper->asset($image);
        if($filter) {
            $browserPath = $this->liipCache->getBrowserPath($browserPath, $filter, $runtimeConfig, $resolver, $referenceType);
        }
        // dump($browserPath);
        return $browserPath;
    }

}