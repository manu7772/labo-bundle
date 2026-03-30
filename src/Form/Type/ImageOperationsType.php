<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Component\ImageOperations;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageOperationsType extends AbstractType
{

    public const CLASSNAME = ImageOperations::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (ImageOperations::getAllOperations() as $name => $values) {
            $builder->add($name, $values['type'], $values['options']);
        }
        // parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaults = [
            'data_class' => static::CLASSNAME,
            // 'mapped' => false,
        ];
        $resolver->setDefaults($defaults);
    }

}