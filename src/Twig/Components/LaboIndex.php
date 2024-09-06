<?php
namespace Aequation\LaboBundle\Twig\Components;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Form\Type\WebpageType;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Exception;

#[AsLiveComponent]
final class LaboIndex extends AbstractController
{
    use DefaultActionTrait;

    public ?ClassmetadataReport $meta_info = null;
    public array $headers = [];

    public array $list = [];
    public ?AppEntityManagerInterface $entityManager = null;

    public function __construct(
        private AppEntityManagerInterface $appEntityManager
    )
    {}

    public function mount(
        ClassmetadataReport $meta_info,
        array $headers = [],
    ): void
    {
        $this->meta_info = $meta_info;
        /** @var ServiceEntityRepository $repository */
        $repository = $this->getManager($meta_info->classname)->getRepository();
        if(empty($headers)) $this->loadHeaders();
        $this->list = $repository->findAll();
    }


    public function getManager(
        string $classname
    ): AppEntityManagerInterface
    {
        return $this->entityManager ??= $this->appEntityManager->getEntityService($classname);
    }

    public function loadHeaders(): void
    {
        $this->headers = ['id', 'name', 'createdAt', 'timezone', 'enabled', 'softdeleted', 'twigfile'];
    }

}
