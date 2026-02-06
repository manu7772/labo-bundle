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
        Overlay::buildForm($builder, $options);
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