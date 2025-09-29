<?php
namespace Aequation\LaboBundle\Controller\Admin\Base;

use DateTime;
use Iterator;
use Exception;
use ReflectionClass;
use Doctrine\ORM\QueryBuilder;
use App\Service\AppEntityManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Aequation\LaboBundle\Component\Opresult;
use Aequation\LaboBundle\Service\AppService;
use phpDocumentor\Reflection\Types\Iterable_;

use Symfony\Component\HttpFoundation\Response;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Strings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\Form\FormBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

use Aequation\LaboBundle\Component\LaboAdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\EventSubscriber\LaboFormsSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Aequation\LaboBundle\Security\Voter\Interface\AppVoterInterface;

use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Aequation\LaboBundle\Component\Interface\LaboAdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;

abstract class BaseCrudController extends AbstractCrudController
{

    public const ENTITY = 'undefined';
    public const VOTER = 'undefined';
    public const DEFAULT_SORT = ['id' => 'ASC'];
    public const THROW_EXCEPTION_WHEN_FORBIDDEN = true;
    public const CUT_NAME_LENGTH = 24;

    public readonly array $query_values;
    public readonly AppEntityManagerInterface $appEntityManager;
    public readonly LaboAdminContextInterface $laboAdminContext;

    public function __construct(
        protected RequestStack $requestStack,
        protected AppEntityManagerInterface $manager,
        protected LaboUserServiceInterface $userService,
        protected TranslatorInterface $translator,
    ) {
        $this->appEntityManager = $manager;
        $this->manager = $manager->getEntityService(static::ENTITY);
        if($manager->isDev()) {
            // [DEV] check entity class service
            $service_class = AppService::getClassServiceName(static::ENTITY);
            if(!is_a($this->manager, (string)$service_class)) throw new Exception(vsprintf('Error %s line %d: manager %s for entity %s is not instance of %s!', [__METHOD__, __LINE__, $this->manager::class, static::ENTITY, $service_class]));
        }
        if($this->userService instanceof LaboUserServiceInterface) {
            $this->userService->addMeToSuperAdmin();
        }
        $query = $this->requestStack->getMainRequest()?->query;
        $this->query_values = $query ? $query->all() : [];
    }

    /**
     * Get the current request/session context
     */

    public function getQueryValues(): array
    {
        return $this->query_values;
    }

    public function getQueryValue(
        string $name,
        mixed $default = null
    ): mixed
    {
        return $this->query_values[$name] ?? $default;
    }

    /**
     * Compile yield fields for the given page
     */

     public static function getFieldsAsIndexedArray(
        iterable $yields
     ): iterable
     {
        $fields = [];
        $eaftindex = 0;
        foreach ($yields as $field) {
            $key = $field->getAsDto()->getProperty();
            switch (true) {
                case $key === 'ea_form_tab':
                    $key .= '---'.$eaftindex++;
                    break;
                case array_key_exists($key, $fields):
                    $key .= '---'.$eaftindex++;
                    break;
            }
            $fields[$key] = $field;
        }
        return $fields;
    }

    protected function recomputeFields(
        iterable $original_fields,
        iterable $new_fields,
        bool $combine = false
    ): Iterator
    {
        $original_fields = $this->getFieldsAsIndexedArray($original_fields);
        // list of all field names
        $field_names = array_combine(array_keys($original_fields), array_keys($original_fields));
        // Add new fields / except 'after' fields
        foreach ($new_fields as $name => $field) {
            if(!isset($field['after'])) $field_names[$name] = $name;
        }
        // Add 'after' fields
        $keys_field_names = array_keys($field_names);
        foreach ($new_fields as $name => $field) {
            if(isset($field['after']) && in_array($field['after'], $field_names)) {
                $key = array_search($field['after'], $keys_field_names) + 1;
                // dd($field['after'], $key, $field_names);
                $field_names = array_merge(
                    array_slice($field_names, 0, $key, true),
                    [$name => $name],
                    array_slice($field_names, $key, null, true)
                );
            }
            if(isset($field['before']) && in_array($field['before'], $field_names)) {
                $key = array_search($field['before'], $keys_field_names);
                // dd($field['after'], $key, $field_names);
                $field_names = array_merge(
                    array_slice($field_names, 0, $key, true),
                    [$name => $name],
                    array_slice($field_names, $key, null, true)
                );
            }
        }
        $newlist = [];
        foreach ($field_names as $name) {
            if(is_string($name)) {
                $field = $new_fields[$name] ?? $combine;
                if(!is_array($field)) {
                    $field = ['field' => $field];
                }
                switch (true) {
                    case $field['field'] === true && isset($original_fields[$name]) && $original_fields[$name] instanceof FieldInterface:
                        if(is_callable($field['action'] ?? null)) {
                            $field['action']($original_fields[$name]);
                        }
                        $newlist[$name] = $original_fields[$name];
                        break;
                    case $field['field'] instanceof FieldInterface:
                        if(is_callable($field['action'] ?? null)) {
                            $field['action']($field['field']);
                        }
                        $newlist[$name] = $field['field'];
                        break;
                    default:
                        # Do not add original field
                        break;
                }
            }
        }
        foreach ($newlist as $name => $field) {
            yield $name => $field;
        }
    }


    public function configureAssets(Assets $assets): Assets
    {
        // $date = new DateTime();
        $assets
            // ->addAssetMapperEntry('eadminlabo')
            ->addCssFile('styles/eadminlabo.css')
            // ->addHtmlContentToHead('<!-- Generated by Aequation Webdesign (with Symfony/EasyAdminBundle) at '.$date->format(DATE_ATOM).' -->')
            ;
        return $assets;
    }

    protected function translate(
        mixed $data,
        array $parameters = [],
        string $domain = 'EasyAdminBundle',
        ?string $locale = null,
    ): mixed
    {
        switch (true) {
            case is_string($data):
                $trans = $this->translator->trans($data, $parameters, $domain, $locale);
                return in_array($trans, ['names', 'name'])
                    ? ucfirst($domain)
                    : $trans;
                break;
            case is_array($data):
                return array_map(function($value) use ($parameters, $domain, $locale) { return $this->translate($value, $parameters, $domain, $locale); }, $data);
                break;
            default:
                return $data;
                break;
        }
        // throw new Exception(vsprintf('Erreur %s ligne %d: la traduction ne peut s\'appliquer qu\'à un texte ou un tableau de textes.'))
    }

    /**
     * Configure Actions
     * @see https://symfony.com/bundles/EasyAdminBundle/current/actions.html
     * @see https://symfonycasts.com/screencast/easyadminbundle1/custom-actions
     * @param Actions $actions
     * @return Actions
     */
    public function configureActions(
        Actions $actions
    ): Actions
    {
        $voter = static::getEntityVoter();

        /*******************************************************************************************/
        /* ADDED ACTIONS
        /*******************************************************************************************/

        $action_lnks = [];
        foreach ($voter::getAddedActionsDescription() as $action_data) {
            $action_lnks[$action_data['name']] = Action::new($action_data['name'],'')
                ->setLabel($this->translate($action_data['label']))
                ->linkToCrudAction($action_data['name'])
                ->setHtmlAttributes(['title' => $this->translate($action_data['title'])])
                ->setIcon($action_data['icon'])
                ->displayAsLink()
                ->displayIf(fn(AppEntityInterface $entity) => $this->isGranted($action_data['action'], $entity))
                ;
        }

        /*******************************************************************************************/
        /* INDEX
        /*******************************************************************************************/

        // REMOVE BATCH DELETE
        if(!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $actions->remove(Crud::PAGE_INDEX, Action::BATCH_DELETE);
        }

        // DETAIL
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        $actions->update(
            Crud::PAGE_INDEX,
            Action::DETAIL,
            fn (Action $action) => $action
                ->setLabel(false)
                ->setIcon('tabler:eye')
                ->setHtmlAttributes(['title' => $this->translate('action.detail')])
                ->displayIf(fn (AppEntityInterface $entity) => $this->isGranted($voter::ACTION_READ, $entity))
        );

        // NEW
        $actions->update(
            Crud::PAGE_INDEX,
            Action::NEW,
            fn (Action $action) => $action
                ->setLabel($this->translate('action.new'))
                ->setIcon('tabler:plus')
                ->displayIf(fn (?AppEntityInterface $entity) => $this->isGranted($voter::ACTION_CREATE, $entity ?? static::ENTITY))
        );

        // EDIT
        $actions->update(
            Crud::PAGE_INDEX,
            Action::EDIT,
            fn (Action $action) => $action
                ->setLabel(false)
                ->setIcon('tabler:pencil')
                ->setHtmlAttributes(['title' => $this->translate('action.edit')])
                ->displayIf(fn (AppEntityInterface $entity) => $this->isGranted($voter::ACTION_UPDATE, $entity))
        );

        // DELETE
        $actions->update(
            Crud::PAGE_INDEX,
            Action::DELETE,
            fn (Action $action) => $action
                ->setLabel(false)
                ->setIcon('tabler:trash')
                ->setHtmlAttributes(['title' => $this->translate('action.delete')])
                ->displayIf(fn (AppEntityInterface $entity) => $this->isGranted($voter::ACTION_DELETE, $entity))
        );

        $actions->reorder(Crud::PAGE_INDEX, [Action::DELETE, Action::EDIT, Action::DETAIL]);

        /*******************************************************************************************/
        /* DETAIL
        /*******************************************************************************************/

        // INDEX
        $actions->update(Crud::PAGE_DETAIL, Action::INDEX, function(Action $action) use ($voter) {
            $action
                ->setLabel($this->translate('action.index'))
                ->setIcon('tabler:list')
                ->setHtmlAttributes(['title' => $this->translate('action.index')])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_LIST, $entity);
                });
            return $action;
        });

        // DELETE
        $actions->update(Crud::PAGE_DETAIL, Action::DELETE, function(Action $action) use ($voter) {
            $action
                // ->setCssClass('btn-danger')
                ->setHtmlAttributes(['title' => 'Supprimer'])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_DELETE, $entity);
                });
            return $action;
        });

        // EDIT
        $actions->update(Crud::PAGE_DETAIL, Action::EDIT, function(Action $action) use ($voter) {
            $action
                ->setIcon('tabler:pencil')
                ->setHtmlAttributes(['title' => 'Éditer'])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_UPDATE, $entity);
                });
            return $action;
        });

        /*******************************************************************************************/
        /* EDIT
        /*******************************************************************************************/

        // INDEX
        $actions->add(Crud::PAGE_EDIT, Action::INDEX);
        $actions->update(Crud::PAGE_EDIT, Action::INDEX, function(Action $action) use ($voter) {
            $action
                ->setLabel('Liste')
                ->setIcon('tabler:list')
                ->setHtmlAttributes(['title' => 'Retour à la liste'])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_LIST, $entity);
                });
            return $action;
        });

        /*******************************************************************************************/
        /* NEW
        /*******************************************************************************************/

        // INDEX
        $actions->add(Crud::PAGE_NEW, Action::INDEX);
        $actions->update(Crud::PAGE_NEW, Action::INDEX, function(Action $action) use ($voter) {
            $action
                ->setLabel('Liste')
                ->setIcon('tabler:list')
                ->setHtmlAttributes(['title' => 'Retour à la liste'])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_LIST, $entity);
                });
            return $action;
        });


        $actions
            ->add(Crud::PAGE_INDEX, $action_lnks['duplicate'])
            ->add(Crud::PAGE_DETAIL, $action_lnks['duplicate'])
            ->reorder(Crud::PAGE_INDEX, [Action::DELETE, 'duplicate', Action::EDIT, Action::DETAIL])
            ;

        // $goToStripe = Action::new('goToStripe')
        //     ->createAsGlobalAction()
        //     ->linkToUrl('https://www.stripe.com/')
        //     ->setHtmlAttributes(['target' => '_blank']);
        // $actions->add(Crud::PAGE_INDEX, $goToStripe);

        return $actions;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->showEntityActionsInlined();
        // Entity name
        $shortname = Classes::getShortname(static::ENTITY);
        $sing_shortname = $this->translate('name', [], $shortname);
        if($sing_shortname === 'name') $sing_shortname = ucfirst(Strings::singularize($shortname));
        $plur_shortname = $this->translate('names', [], $shortname);
        if($plur_shortname === 'names') $plur_shortname = ucfirst(Strings::pluralize($shortname));
        // Configure Crud
        $crud
            ->setDefaultSort(static::DEFAULT_SORT)
            ->overrideTemplates([
                'crud/field/id' => '@EasyAdmin/crud/field/id_with_icon.html.twig',
                // 'crud/field/thumbnail' => '@EasyAdmin/crud/field/template.html.twig',
            ])
            ->setPageTitle('index', '<small>Liste des </small>%entity_label_plural%')
            ->setPageTitle('detail', '%entity_label_singular%')
            ->setPageTitle('edit', '<small>Modifier </small>%entity_label_singular%')
            ->setPageTitle('new', '<small>Créer </small>%entity_label_singular%')
            ->setTimezone($this->manager->getAppService()->getAppContext()->getTimezone()->getName())
            // ->setEntityLabelInSingular(Strings::singularize($shortname))
            // ->setEntityLabelInPlural(Strings::pluralize($shortname))
            ->setEntityLabelInSingular(
                fn (?AppEntityInterface $entity, ?string $pageName) => $entity ? '<small>'.$sing_shortname.' </small>'.Strings::cutAt($entity->__toString(), static::CUT_NAME_LENGTH, true) : $sing_shortname
            )
            ->setEntityLabelInPlural(
                fn (?AppEntityInterface $entity, ?string $pageName) => $pageName && in_array($pageName, ['edit','detail']) ? '<small>'.$plur_shortname.' </small>'.Strings::cutAt($entity->__toString(), static::CUT_NAME_LENGTH, true) : $plur_shortname
            )
            // ->hideNullValues()
            // ->setFormOptions([
            //     'attr' => ['class' => 'text-info']
            // ])
            // ->renderSidebarMinimized()
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->renderContentMaximized()
            ->setPaginatorPageSize(20)
            ;
        return $crud;
    }

    public function duplicate(
        AdminContext $context,
    )
    {
        $request = $this->container->get('request_stack')->getMainRequest();
        $request->query->set('crudAction', Action::NEW);
        $request->query->set('crudControllerFqcn', static::class);
        $request->query->remove('entityId');
        // return $this->new($context);
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::NEW, 'entity' => null])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        // $csrfToken = $context->getRequest()->request->get('token');
        // if ($this->container->has('security.csrf.token_manager') && !$this->isCsrfTokenValid('ea-duplicate', $csrfToken)) {
        //     return $this->redirectToRoute($context->getDashboardRouteName());
        // }

        // Get clone from original entity
        /** @var AppEntityInterface $model */
        $model = $context->getEntity()->getInstance();
        /** @var AppEntityInterface $duplicate */
        // $duplicate = $this->manager->getClone($model);
        $duplicate = clone $model;
        $context->getEntity()->setInstance($duplicate);
        // $context->getEntity()->setInstance($this->createEntity($context->getEntity()->getFqcn()));
        $context->getCrud()->setPageName(Crud::PAGE_NEW);
        $context->getCrud()->getActionsConfig()->setPageName(Crud::PAGE_NEW);
        $this->container->get(EntityFactory::class)->processFields($context->getEntity(), FieldCollection::new($this->configureFields(Crud::PAGE_NEW)));
        $context->getCrud()->setFieldAssets($this->getFieldAssets($context->getEntity()->getFields()));
        $this->container->get(EntityFactory::class)->processActions($context->getEntity(), $context->getCrud()->getActionsConfig());

        $newForm = $this->createNewForm($context->getEntity(), $context->getCrud()->getNewFormOptions(), $context);
        $newForm->handleRequest($context->getRequest());

        $entityInstance = $newForm->getData();
        $context->getEntity()->setInstance($entityInstance);

        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $this->processUploadedFiles($newForm);

            $event = new BeforeEntityPersistedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->persistEntity($this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entityInstance);

            $this->container->get('event_dispatcher')->dispatch(new AfterEntityPersistedEvent($entityInstance));
            $context->getEntity()->setInstance($entityInstance);

            return $this->getRedirectResponseAfterSave($context, Action::NEW);
        }

        // if (null !== $referrer = $context->getReferrer()) {
        //     return $this->redirect($referrer);
        // }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_NEW,
            'templateName' => 'crud/new',
            'entity' => $context->getEntity(),
            'new_form' => $newForm,
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public static function getEntityFqcn(): string
    {
        $class = static::ENTITY;
        // if(!is_a($class, AppEntityInterface::class, true)) throw new Exception(vsprintf('Error %s line %d: this class %s is not instance of %s!', [__METHOD__, __LINE__, $class, AppEntityInterface::class]));
        return $class;
    }

    public static function getEntityVoter(): string
    {
        $class = static::VOTER;
        // if(!is_a($class, AppVoterInterface::class, true)) throw new Exception(vsprintf('Error %s line %d: this class %s is not instance of %s!', [__METHOD__, __LINE__, $class, AppVoterInterface::class]));
        return $class;
    }

    protected function getLaboContext(): ?LaboAdminContextInterface
    {
        return $this->laboAdminContext ??= new LaboAdminContext($this, $this->getContext());
    }

    // protected function getContextInfo(): array
    // {
    //     trigger_deprecation(
    //         'aequation\labo-bundle',
    //         '2.0.0',
    //         'This method '.__METHOD__.' is deprecated. Use LaboAdminContext you can get from $this->getLaboContext() instead.',
    //         __METHOD__,
    //     );
    //     $info = [
    //         'user' => null,
    //         'entityDto' => null,
    //         'classname' => null,
    //         'entity' => null,
    //     ];
    //     $context = $this->getContext();
    //     if($context) {
    //         $info['user']       = $context->getUser() ?? $this->getUser();
    //         $info['entityDto']  = $context->getEntity();
    //         $info['classname']  = $context->getEntity()->getFqcn();
    //         $this->getLaboContext()->getInstance()     = $context->getEntity()->getInstance() ?? $info['classname'];
    //         // if(empty($this->getLaboContext()->getInstance())) {
    //         //     // $this->getLaboContext()->getInstance() = $this->createEntity(entityFqcn: $info['classname'], checkGrant: false);
    //         //     $this->getLaboContext()->getInstance() = $this->manager instanceof AppEntityManagerInterface
    //         //         ? $this->manager->getModel()
    //         //         : new $info['classname'];
    //         // }
    //         $RC = new ReflectionClass($info['classname']);
    //         $info['instantiable'] = $RC->isInstantiable();
    //     }
    //     return $info;
    // }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if(!$this->isGranted('ROLE_SUPER_ADMIN') && is_a($this->getLaboContext()->getInstance(), EnabledInterface::class, true)) {
            $queryBuilder->andWhere('entity.softdeleted = false');
        }
        return $queryBuilder;
    }

    /**
     * Throws exception if PAGE ACCESS is not granted for current user
     * @param string $pageName
     * @return Opresult
     */
    public function checkGrants(
        string $pageName,
        ?bool $make_exception = null
    ): Opresult
    {
        if(!is_bool($make_exception)) $make_exception = static::THROW_EXCEPTION_WHEN_FORBIDDEN;
        $opresult = new Opresult();
        $voter = $this->getEntityVoter();
        if($this->getLaboContext()->isInstantiable() && is_a($voter, VoterInterface::class, true)) {
            switch ($pageName) {
                case Crud::PAGE_DETAIL:
                    if(!$this->isGranted(attribute: $voter::ACTION_READ, subject: $this->getLaboContext()->getInstanceOrClass())) {
                        $message = vsprintf('Vous n\'êtes pas autorisé consulter cet élément %s.', [$this->getLaboContext()->getInstance()?->__toString() ?? $this->getLaboContext()->getEntity()->getFqcn()]);
                        if($make_exception) throw new Exception(message: $message, code: 403);
                        $this->addFlash('danger', $message);
                        $opresult->addDanger($message);
                    }
                    break;
                case Crud::PAGE_NEW:
                    if(!$this->isGranted(attribute: $voter::ACTION_CREATE, subject: $this->getLaboContext()->getInstanceOrClass())) {
                        $message = vsprintf('Vous n\'êtes pas autorisé à réaliser cette création (%s).', [$this->getLaboContext()->getEntity()->getFqcn()]);
                        if($make_exception) throw new Exception(message: $message, code: 403);
                        $this->addFlash('danger', $message);
                        $opresult->addDanger($message);
                    }
                    break;
                case Crud::PAGE_EDIT:
                    if(!$this->isGranted(attribute: $voter::ACTION_UPDATE, subject: $this->getLaboContext()->getInstanceOrClass())) {
                        $message = vsprintf('Vous n\'êtes pas autorisé à réaliser cette modification de %s.', [$this->getLaboContext()->getInstance()?->__toString() ?? $this->getLaboContext()->getEntity()->getFqcn()]);
                        if($make_exception) throw new Exception(message: $message, code: 403);
                        $this->addFlash('danger', $message);
                        $opresult->addDanger($message);
                    }
                    break;
                default:
                if(!$this->isGranted(attribute: $voter::ACTION_LIST, subject: $this->getLaboContext()->getInstanceOrClass())) {
                    $message = vsprintf('Vous n\'êtes pas autorisé à réaliser cette opération "%s" sur l\'entité %s.', [$pageName, $this->getLaboContext()->getEntity()->getFqcn()]);
                    if($make_exception) throw new Exception(message: $message, code: 403);
                    $this->addFlash('danger', $message);
                    $opresult->addDanger($message);
                }
                break;
            }
        }
        return $opresult;
    }


    /************************************************************* */

    public function createEntity(
        string $entityFqcn,
        bool $checkGrant = true,
    ): ?AppEntityInterface
    {
        if($checkGrant) $this->checkGrants(Crud::PAGE_NEW);
        $RC = new ReflectionClass($entityFqcn);
        if($RC->isInstantiable()) {
            $entity = $this->manager instanceof AppEntityManagerInterface
                ? $this->manager->getNew($entityFqcn)
                : new $entityFqcn();
            return $entity;
        }
        return null;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->manager->save($entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->manager->save($entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->manager->delete($entityInstance);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $entity = $formBuilder->getData();
        if($entity instanceof AppEntityInterface) {
            $formBuilder->addEventSubscriber(new LaboFormsSubscriber($this->manager));
        }
        return $formBuilder;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        // $formBuilder->setEmptyData($this->createEntity($entityDto->getFqcn(), false));
        $entity = $formBuilder->getData();
        if($entity instanceof AppEntityInterface) {
            $formBuilder->addEventSubscriber(new LaboFormsSubscriber($this->manager));
        }
        return $formBuilder;
    }

}