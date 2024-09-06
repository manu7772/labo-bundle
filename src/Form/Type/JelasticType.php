<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Component\Jelastic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\Event;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class JelasticType extends AbstractType
{

    public const CLASSNAME = Jelastic::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);
        $data = $builder->getData();

        $builder
            ->add('name', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['placeholder' => 'Nom'],
                'by_reference' => false,
            ])
            ->add('displayName', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Nom de configuration'],
                'by_reference' => false,
            ])
            ->add('envGroups', ChoiceType::class, [
                'label' => 'Groupes',
                'required' => false,
                'choices' => $data->getEnvGroupsChoices(),
                // 'placeholder' => 'Groupes',
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
            ])
            ->add('homepage', UrlType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Git repository page'],
                'by_reference' => false,
            ])
            ->add('baseUrl', UrlType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Git repository documentation page'],
                'by_reference' => false,
            ])
            ->add('dbname', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['placeholder' => 'Base de données'],
                'by_reference' => false,
            ])
            ->add('dbuser', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['placeholder' => 'Nom utilisateur de la base de données'],
                'by_reference' => false,
            ])
            ->add('dbpwd', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => ['placeholder' => 'Mot de passe utilisateur de la base de données'],
                'by_reference' => false,
                // 'toggle' => true,
                // 'hidden_label' => 'Masquer',
                // 'visible_label' => 'Afficher',
                // 'always_empty' => false, // REQUIRED with live component form!!! DO NOT REMOVE!!!
            ])
            ->add('usenpm', CheckboxType::class, [
                'label' => 'Installer NPM',
                'required' => false,
                'attr' => ['role' => 'switch'],
                'by_reference' => false,
            ])
            ->add('usemailcatcher', CheckboxType::class, [
                'label' => 'Installer MailCatcher',
                'required' => false,
                'attr' => ['role' => 'switch'],
                'by_reference' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            // dd($event->getData());
            $model = new Jelastic();
            $event->setData($model->computeData($event->getData()));
            // $form = $event->getForm();
        });

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
