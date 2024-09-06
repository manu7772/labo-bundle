<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\LaboRelink;
use Aequation\LaboBundle\Form\base\BaseAppType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class LaboRelinkType extends BaseAppType
{
    // public const CLASSNAME = LaboRelink::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('linktitle')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('timezone')
            ->add('enabled')
            ->add('softdeleted')
            ->add('url')
            ->add('route')
            ->add('params')
            ->add('target')
            ->add('turboenabled')
            ->add('parentrelink', EntityType::class, [
                'class' => LaboRelink::class,
                'choice_label' => 'id',
            ])
        ;
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => static::CLASSNAME,
        ]);
    }
}
