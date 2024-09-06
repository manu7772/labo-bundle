<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Service\Interface\FormServiceInterface;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\Form\FormInterface;

#[AsAlias(FormServiceInterface::class, public: true)]
class FormService extends BaseService implements FormServiceInterface
{

    public const TYPES = ['primary','secondary','info','success','warning','danger'];


    public function __construct(
        public readonly FormFactoryInterface $formFactory
    ) {}

    public function getForm(
        string $type = FormType::class,
        mixed $data = null,
        array $options = [],
    ): FormInterface
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * Get value of clicked submit button in Form
     * @param Form $form
     * @return string
     */
    public static function findAction(
        Form $form
    ): string
    {
        $action = null;
        foreach ($form->all() as $subForm) {
            if($subForm instanceof SubmitButton && ($subForm->isSubmitted() || $subForm->isClicked())) {
                $attr = $subForm->getConfig()->getOption('attr');
                return $attr['value'] ?? $action;
            }
        }
        return $action;
    }

    /**
     * Get informations on submitted input in Form
     * @param FormInterface $form
     * @return string[]
     */
    public static function isSubmittedClickedByUser(
        FormInterface $form
    ): bool
    {
        if(!$form->isSubmitted()) return false;
        foreach ($form->all() as $subForm) {
            if($subForm instanceof SubmitButton && $subForm->isClicked()) return true;
        }
        return false;
    }

    /**
     * Get informations on submitted input in Form
     * @param FormInterface $form
     * @return string[]
     */
    public static function getSubmitInfo(
        FormInterface $form
    ): array
    {
        $infos = [
            'action' => null,
            'type' => null,
        ];
        foreach ($form->all() as $subForm) {
            if($subForm instanceof SubmitButton && $subForm->isClicked()) {
                $attr = $subForm->getConfig()->getOption('attr');
                $infos['action'] = $attr['value'] ?? null;
                if(preg_match('/('.implode('|', static::TYPES).')/', $attr['class'] ?? '', $matches)) {
                    $infos['type'] = in_array($matches[1], static::TYPES) ? $matches[1] : $matches[0];
                }
            }
        }
        if($infos['type'] && !in_array($infos['type'], static::TYPES)) $infos['type'] = reset(static::TYPES);
        return $infos;
    }

    public static function hasFormSubmitCliqued(
        FormInterface $form
    ): bool
    {
        foreach ($form->all() as $subForm) {
            if($subForm instanceof SubmitButton && ($subForm->isSubmitted() || $subForm->isClicked())) {
                return true;
            }
        }
        return false;
    }

}