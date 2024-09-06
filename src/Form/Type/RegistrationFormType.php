<?php

namespace Aequation\LaboBundle\Form\Type;

// use App\Entity\User;

use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Form\base\BaseAppType;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends BaseAppType
{
    public const CLASSNAME = User::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(child: 'email', type: EmailType::class, options: [
                'attr' => ['autofocus' => true],
                'priority' => 900,
            ])
            ->add(child: 'firstname', type: TextType::class, options: [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Indiquer un nom, svp.',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 64,
                        'maxMessage' => 'Le nom doit contenir {{ limit }} caractères au maximum',
                    ]),
                ],
                'priority' => 800,
            ])
            ->add(child: 'plainPassword', type: PasswordType::class, options: [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                // 'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Indiquer un mot de passe, svp.',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 256,
                    ]),
                ],
                'always_empty' => false, // REQUIRED with live component form!!! DO NOT REMOVE!!!
                'priority' => 600,
            ])
            ->add(child: 'agreeTerms', type: CheckboxType::class, options: [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Validez ici pour accepter les termes d\'utilisation.',
                    ]),
                ],
                'priority' => 400,
            ])
            ->add(child: 'submit', type: SubmitType::class, options: [
                'label' => 'Inscription',
                'priority' => -100,
            ]
        );

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => static::CLASSNAME,
            'attr' => [
                'data-action' => 'live#action:prevent',
                'data-live-action-param' => 'save',        
            ],
        ]);
    }
}
