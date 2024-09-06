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

class BaseAppType extends AbstractType
{

    public const CLASSNAME = AppEntityInterface::class;
    public const FORM_CLASS = [
        '@defaults' => 'tailwind-form',
    ];

    protected AppEntityManagerInterface $manager;

    public function __construct(
        protected AppService $appService,
        #[Autowire(param: 'kernel.environment')]
        protected $environment
    ) {
        $this->manager = $appService->get(AppEntityManagerInterface::class)->getEntityService(static::CLASSNAME);
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
    ): string
    {
        $form_class = static::getFormClasses();
        $index = array_key_exists($index, $form_class) ? $index : array_key_first($form_class);
        return $form_class[$index];
    }

    #[CssClasses(target: 'value')]
    public static function getFormClasses(): array
    {
        return static::FORM_CLASS;
    }

}