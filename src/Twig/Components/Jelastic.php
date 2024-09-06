<?php
namespace Aequation\LaboBundle\Twig\Components;

use Aequation\LaboBundle\Component\Jelastic as ComponentJelastic;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent]
final class Jelastic extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    /**
     * @see https://ux.symfony.com/demos/live-component/product-form
     */
    // use ValidatableComponentTrait;

    public function __construct(
        protected LaboBundleServiceInterface $laboService
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->laboService->getJelasticForm();
    }

    #[LiveAction]
    public function saveRegistration()
    {
        $this->submitForm();
        $this->getCode();
    }

    public function getCode(): string|false
    {
        if(!$this->form->isSubmitted() || ($this->form->isSubmitted() && $this->form->isValid())) {
            $data['data'] = $this->getData();
            return $this->laboService->getJelasticFile($data);
        }
        return false;
    }

    public function getData(): ComponentJelastic
    {
        return $this->form->getData();
    }

}
