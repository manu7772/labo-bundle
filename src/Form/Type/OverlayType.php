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
                'expanded' => false,
            ])
            // TITLE text
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => false,
            ])
            // TITLE size
            ->add('title_size', ChoiceType::class, [
                'label' => 'Taille du titre',
                'required' => Overlay::isTitleSizeRequired(),
                'choices' => Overlay::getTitleSizeChoices(),
                'multiple' => Overlay::isTitleSizeMultiple(),
                'expanded' => false,
            ])
            // TITLE style
            ->add('title_style', ChoiceType::class, [
                'label' => 'Style du titre',
                'required' => Overlay::isTitleStyleRequired(),
                'choices' => Overlay::getTitleStyleChoices(),
                'multiple' => Overlay::isTitleStyleMultiple(),
                'expanded' => false,
            ])
            // TITLE align
            ->add('title_align', ChoiceType::class, [
                'label' => 'Alignement du titre',
                'required' => Overlay::isTitleAlignRequired(),
                'choices' => Overlay::getTitleAlignChoices(),
                'multiple' => Overlay::isTitleAlignMultiple(),
                'expanded' => false,
            ])
            // TITLE font
            ->add('title_font', ChoiceType::class, [
                'label' => 'Police du titre',
                'required' => Overlay::isTitleFontRequired(),
                'choices' => Overlay::getTitleFontChoices(),
                'multiple' => Overlay::isTitleFontMultiple(),
                'expanded' => false,
            ])
            // TEXT
            ->add('text', TextareaType::class, [
                'label' => 'Texte',
                'required' => false,
            ])
            // TEXT size
            ->add('text_size', ChoiceType::class, [
                'label' => 'Taille du texte',
                'required' => Overlay::isTextSizeRequired(),
                'choices' => Overlay::getTextSizeChoices(),
                'multiple' => Overlay::isTextSizeMultiple(),
                'expanded' => false,
            ])
            // TEXT style
            ->add('text_style', ChoiceType::class, [
                'label' => 'Style du texte',
                'required' => Overlay::isTextStyleRequired(),
                'choices' => Overlay::getTextStyleChoices(),
                'multiple' => Overlay::isTextStyleMultiple(),
                'expanded' => false,
            ])
            // TEXT align
            ->add('text_align', ChoiceType::class, [
                'label' => 'Alignement du texte',
                'required' => Overlay::isTextAlignRequired(),
                'choices' => Overlay::getTextAlignChoices(),
                'multiple' => Overlay::isTextAlignMultiple(),
                'expanded' => false,
            ])
            // TEXT font
            ->add('text_font', ChoiceType::class, [
                'label' => 'Police du texte',
                'required' => Overlay::isTextFontRequired(),
                'choices' => Overlay::getTextFontChoices(),
                'multiple' => Overlay::isTextFontMultiple(),
                'expanded' => false,
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