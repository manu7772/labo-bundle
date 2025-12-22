<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Component\Overlay;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OverlayType extends AbstractType
{
    public const CLASSNAME = Overlay::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', ChoiceType::class, [
                'label' => 'Position',
                'required' => true,
                'choices' => Overlay::getPositionChoices(),
                'multiple' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => false,
            ])
            ->add('title_classes', ChoiceType::class, [
                'label' => 'Style de titre',
                'required' => false,
                'choices' => Overlay::getTitleClassesChoices(),
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Texte',
                'required' => false,
            ])
            ->add('text_classes', ChoiceType::class, [
                'label' => 'Style de texte',
                'required' => false,
                'choices' => Overlay::getTextClassesChoices(),
                'multiple' => true,
                'expanded' => true,
            ])
            ;
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaults = [
            'data_class' => static::CLASSNAME,
            // 'mapped' => false,
            // 'attr' => [
            //     'class' => 'tailwind-form',
            //     'data-action' => 'live#action',
            //     'data-action-name' => 'prevent|save',
            // ],
        ];
        $resolver->setDefaults(defaults: $defaults);
    }
}