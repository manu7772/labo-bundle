<?php
namespace Aequation\LaboBundle\Form\Type;

use Symfony\Component\Form\FormEvent;
use Aequation\LaboBundle\Entity\Image;
use Symfony\Component\Form\FormEvents;

use Aequation\LaboBundle\Form\base\BaseAppType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
use Aequation\LaboBundle\Service\Interface\ImageServiceInterface;

class ImageType extends BaseAppType
{
    public const CLASSNAME = Image::class;
    public const DELETE_IMAGE = true;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $class = $builder->getDataClass();
        /** @var ImageServiceInterface */
        $manager = $this->manager;
        $builder
            ->add('name', TextType::class, [
                'label' => 'nom',
            ])
            ->add('file', VichImageType::class, [
                'label' => false,
                'allow_delete' => false,
            ])
            ->add('imagefilter', ChoiceType::class, [
                'label' => 'Format d\'affichage',
                'required' => false,
                'placeholder' => 'Choisissez un format d\'affichage par défaut',
                'choices' => $manager->getLiipFilterChoices(400, 300, $builder->getData()),
                'choice_translation_domain' => 'messages',
                'empty_data' => $manager->getDefaultLiipFilterName($builder->getData() ?? $builder->getDataClass()),
            ])
        ;
        parent::buildForm($builder, $options);
    }

}