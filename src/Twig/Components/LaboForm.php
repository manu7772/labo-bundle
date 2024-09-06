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
        // dump($this);
        // if($this->entity instanceof AppEntityInterface) {
        //     if(!empty($this->classname) && $this->classname !== $this->entity->getClassname()) {
        //         // Entity and classname does not match!
        //         throw new Exception(vsprintf('Error on %s line %d: entity of class %s does not match given classname %s!', [__METHOD__, __LINE__, $this->entity->getClassname(), $this->classname]));
        //     }
        //     $this->classname = $this->entity->getClassname();
        // } else if(is_string($this->classname)) {
        //     if(!$this->appEntityManager->entityExists($this->classname) && !is_a($this->classname, AppEntityInterface::class, true)) {
        //         throw new Exception(vsprintf('Error on %s line %d: given classname %s should be instance of %s!', [__METHOD__, __LINE__, $this->classname, AppEntityInterface::class]));
        //     }
        //     $this->entity = $this->appEntityManager->getNew($this->classname);
        // } else {
        //     throw new Exception(vsprintf('Error on %s line %d: please give an object entity or a classname of entity!', [__METHOD__, __LINE__]));
        // }
        // // finally...
        // $this->entityManager = $this->appEntityManager->getEntityService($this->classname);
        // $this->meta_info = $this->entityManager->getEntityMetadataReport();
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
        // dump($this->entity);
        return $this->createForm(WebpageType::class, $this->entity);
    }

    public function getMeta_info(): ?ClassmetadataReport
    {
        return $this->meta_info ?? null;
    }

}
