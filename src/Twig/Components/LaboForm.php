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

use Exception;

#[AsLiveComponent]
final class LaboForm extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(useSerializerForHydration: true)]
    public ?AppEntityInterface $entity = null;
    // public ?string $classname = null;
    private AppEntityManagerInterface $entityManager;
    private ClassmetadataReport $meta_info;

    public function __construct(
        private AppEntityManagerInterface $appEntityManager
    )
    {
    }

    public function getEntity(): ?AppEntityInterface
    {
        return $this->entity ?? null;
    }

    public function getShortname(): ?string
    {
        return $this->getMeta_info()?->getShortname(true) ?? null;
    }

    public function instantiateForm(): FormInterface
    {
        return $this->createForm(WebpageType::class, $this->entity);
    }

    public function getMeta_info(): ?ClassmetadataReport
    {
        return $this->meta_info ?? null;
    }

}
