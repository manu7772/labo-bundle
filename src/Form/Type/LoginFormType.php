<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Component\LoginContainer;
use Aequation\LaboBundle\Validator\UserExists;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginFormType extends AbstractType
{

    public function __construct(
        #[Autowire(param: 'kernel.environment')] private $environment
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var LoginContainer $data */
        $data = $builder->getData();
        // dd($data);
        $builder
            ->add(child: 'email', type: EmailType::class, options: [
                'label' => false,
                'required' => true,
                'attr' => [
                    // 'class' => 'mb-3', // some space for toogle password...
                    'autofocus' => true,
                    'placeholder' => 'email',
                    'autocomplete' => 'username',
                    'data-action' => 'blur->live#update',
                    // 'data-action-name' => 'searchEmail', // Available modifiers are: prevent, stop, self, debounce, files.
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez indiquer votre email, svp.',
                    ]),
                    new Email([
                        'message' => 'Cet email est invalide.',
                    ]),
                    new UserExists(),
                ],
                'priority' => 900,
            ])
            ->add(child: 'password', type: PasswordType::class, options: [
                'label' => false,
                'required' => true,
                // 'toggle' => true,
                'attr' => [
                    'placeholder' => 'password',
                    'autocomplete' => 'current-password',
                ],
                'always_empty' => false, // REQUIRED with live component form!!! DO NOT REMOVE!!!
                'constraints' => [
                    new NotBlank([
                        'message' => 'Indiquer votre mot de passe, svp.',
                    ]),
                ],
                'priority' => 800,
            ])
            // ->add(child: 'sendmailvalid', type: HiddenType::class, options: [
            //     'required' => false,
            //     'priority' => -20,
            // ])
            // ->add(child: '_csrf_token', type: HiddenType::class, options: [
            //     'label' => false,
            //     'priority' => -30,
            // ])
            ->add(child: 'sendmail', type: SubmitType::class, options: [
                'label' => 'Recevoir un mail de connexion',
                'disabled' => !$data->getSendmail(),
                'priority' => -50,
            ])
            ->add(child: 'login', type: SubmitType::class, options: [
                'label' => 'Connexion',
                'disabled' => !$data->isValid(),
                'priority' => -100,
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaults = [
            'data_class' => LoginContainer::class,
            'attr' => [
                'class' => 'tailwind-form',
                // 'data-action' => 'live#action',
                // 'data-action-name' => 'prevent|debounce(300)|login',
            ],
        ];
        if(strtolower($this->environment) !== 'prod') {
            $defaults['attr']['novalidate'] = true;
        }
        $resolver->setDefaults(defaults: $defaults);
    }
}