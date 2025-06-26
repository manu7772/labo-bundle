<?php
namespace Aequation\LaboBundle\Form\base;

use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\EventSubscriber\LaboFormsSubscriber;
use Aequation\LaboBundle\Model\Attribute\CssClasses;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Service\AppEntityManager;
use Aequation\LaboBundle\Service\AppService;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BaseAppType extends AbstractType
{

    public const CLASSNAME = AppEntityInterface::class;
    public const FORM_CLASS = [
        // '@defaults' => 'tailwind-form',
        // 'data-action' => 'live#action',
        // 'data-action-name' => 'prevent|save',
    ];

    protected AppEntityManagerInterface $manager;
    protected AppEntityManagerInterface $laboEm;

    public function __construct(
        protected AppService $appService,
        #[Autowire(param: 'kernel.environment')]
        protected $environment
    ) {
        $this->laboEm = $this->appService->get(AppEntityManagerInterface::class);
        $this->manager = $this->laboEm->getEntityService(static::CLASSNAME);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // parent::buildForm($builder, $options);
        $this->addAppEvents(formBuilder: $builder, options: $options);
    }

    protected function addAppEvents(
        FormBuilderInterface $formBuilder,
        array $options,
    ): void
    {
        $formBuilder->addEventSubscriber(new LaboFormsSubscriber($this->manager));
    }

    protected function isValidEntity(AppEntityInterface $data): bool
    {
        /** @var AppEntityManager $manager */
        $manager = $this->manager;
        return $manager->isValidEntity($data);
    }

    public static function getFormClass(
        ?string $index = null
    ): ?string
    {
        $form_class = static::getFormClasses();
        $index = array_key_exists($index, $form_class) ? $index : array_key_first($form_class);
        return $form_class[$index] ?? null;
    }

    #[CssClasses(target: 'value')]
    public static function getFormClasses(): array
    {
        return static::FORM_CLASS;
    }

    public function getBaseDefaults(): array
    {
        $defaults = [
            'data_class' => static::CLASSNAME,
            'empty_data' => fn(FormInterface $form): AppEntityInterface => $this->manager->getNew(),
            // 'imagine_pattern' => 'tiny_q',
        ];
        if($attr_class = $this->getFormClass()) {
            $defaults['attr']['class'] = $attr_class;
        }
        return $defaults;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults($this->getBaseDefaults());
    }

}