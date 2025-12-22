<?php

namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Form\base\BaseAppType;
use Aequation\LaboBundle\Model\Attribute\CssClasses;
use Aequation\LaboBundle\Service\Tools\Times;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends BaseAppType
{
    public const CLASSNAME = User::class;
    public const FORM_CLASS = [
        // '@defaults' => 'tailwind-form',
        'data-action' => 'live#action',
        'data-action-name' => 'prevent|save',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User */
        $user = $builder->getData();
        $builder
            ->add(child: 'email', type: EmailType::class, options: [
                'constraints' => [
                    new Email([
                        'message' => 'Cet email est invalide.',
                    ]),
                ],
                'priority' => 900,
            ])
            ->add(child: 'firstname', type: TextType::class, options: [
                'attr' => ['autofocus' => true],
                'required' => true,
                'constraints' => [
                    new Regex([
                        'pattern' => '/\\w+/',
                        'message' => 'Le nom indiqué est incorrect.',
                        // 'htmlPattern' => 
                        'match' => true,
                        'normalizer' => fn ($value): string => trim($value),
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 64,
                        'maxMessage' => 'Le nom doit contenir {{ limit }} caractères au maximum',
                    ]),
                    new NotBlank([
                        'message' => 'Indiquer un nom, svp.',
                    ]),
                ],
                'priority' => 700,
            ])
            ->add(child: 'lastname', type: TextType::class, options: [
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 64,
                        'maxMessage' => 'Le prénom doit contenir {{ limit }} caractères au maximum',
                    ]),
                ],
                'priority' => 500,
            ])
            ->add(child: 'timezone', type: ChoiceType::class, options: [
                'choices' => Times::getTimezoneChoices(),
                'multiple' => false,
                'expanded' => false,
                'priority' => 100,
            ])
            ->add(child: 'submit', type: SubmitType::class, options: [
                'label' => 'Enregistrer',
                'priority' => -100,
            ])
        ;

        if($this->appService->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add(child: 'roles', type: ChoiceType::class, options: [
                'choices' => $user->getRolesChoices(),
                'multiple' => true,
                'expanded' => true,
                'priority' => 300,
            ]);
        }

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaults = $this->getBaseDefaults();
        if(strtolower($this->environment) !== 'prod') {
            $defaults['attr']['novalidate'] = true;
        }
        $resolver->setDefaults($defaults);
    }

}
