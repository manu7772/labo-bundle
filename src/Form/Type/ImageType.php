<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Form\base\BaseAppType;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ImageType extends BaseAppType
{
    public const CLASSNAME = Image::class;
    public const DELETE_IMAGE = true;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $class = $builder->getDataClass();
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
                'required' => true,
                'placeholder' => 'Choisissez un format d\'affichage par défaut',
                'choices' => $class::getLiipFilterChoices(),
                'choice_translation_domain' => 'messages',
            ])
        ;
        parent::buildForm($builder, $options);
    }

}