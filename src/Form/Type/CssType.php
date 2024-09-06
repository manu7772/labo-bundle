<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Component\CssManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CssType extends AbstractType
{

    public const CLASSNAME = CssManager::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stringCssClasses', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['placeholder' => 'Entrez ici les classes css que vous souhaitez ajouter...'],
            ])
            // ->add('submit', SubmitType::class, [
            //     'label' => 'Enregistrer',
            // ])
            ;
        
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaults = [
            'data_class' => static::CLASSNAME,
            // 'mapped' => false,
            'method' => 'POST',
        ];
        $resolver->setDefaults(defaults: $defaults);
    }

}