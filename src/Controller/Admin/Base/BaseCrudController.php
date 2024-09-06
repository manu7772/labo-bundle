<?php
namespace Aequation\LaboBundle\Controller\Admin\Base;

use Aequation\LaboBundle\Component\Opresult;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\EventSubscriber\LaboFormsSubscriber;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
use Aequation\LaboBundle\Security\Voter\Interface\AppVoterInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Strings;

use App\Service\AppEntityManager;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Exception;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

abstract class BaseCrudController extends AbstractCrudController
{

    public const ENTITY = 'undefined';
    public const VOTER = 'undefined';
    public const DEFAULT_SORT = ['id' => 'ASC'];
    public const THROW_EXCEPTION_WHEN_FORBIDDEN = true;

    protected AppEntityManagerInterface $manager;

    public function __construct(
        AppEntityManagerInterface $manager,
        protected LaboUserServiceInterface $userService,
    ) {
        $this->manager = $manager;
        if($this->userService instanceof LaboUserServiceInterface) {
            $this->userService->addMeToSuperAdmin();
        }
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
                ->linkToCrudAction($action_data['name'])
                ->setHtmlAttributes(['title' => ucfirst($action_data['name'])])
                ->setIcon('fa fa-copy')
                ->displayAsLink()
                ->displayIf(fn(AppEntityInterface $entity) => $this->isGranted($action_data['action'], $entity))
                // ->displayIf(fn(AppEntityInterface $entity) => !preg_match('/(\s-\scopie\d+)$/', $entity->getName()))
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
        $actions->update(Crud::PAGE_INDEX, Action::DETAIL, function(Action $action) use ($voter) {
            $action
                ->setLabel('')
                ->setIcon('fa fa-eye')
                ->setHtmlAttributes(['title' => 'Consulter'])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_READ, $entity);
                });
            return $action;
        });

        // NEW
        $actions->update(Crud::PAGE_INDEX, Action::NEW, function(Action $action) use ($voter) {
            $action
                ->setLabel('Nouveau')
                ->setIcon('fa fa-plus')
                ->displayIf(function (?AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_CREATE, $entity ?? static::ENTITY);
                });
            return $action;
        });

        // EDIT
        $actions->update(Crud::PAGE_INDEX, Action::EDIT, function(Action $action) use ($voter) {
            $action
                ->setLabel('')
                ->setIcon('fa fa-pencil')
                ->setHtmlAttributes(['title' => 'Éditer'])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_UPDATE, $entity);
                });
            return $action;
        });

        // DELETE
        $actions->update(Crud::PAGE_INDEX, Action::DELETE, function(Action $action) use ($voter) {
            $action
                ->setLabel('')
                ->setIcon('fa fa-trash text-muted')
                ->setHtmlAttributes(['title' => 'Supprimer'])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_DELETE, $entity);
                });
            return $action;
        });

        $actions->reorder(Crud::PAGE_INDEX, [Action::DELETE, Action::EDIT, Action::DETAIL]);

        /*******************************************************************************************/
        /* DETAIL
        /*******************************************************************************************/

        // INDEX
        $actions->update(Crud::PAGE_DETAIL, Action::INDEX, function(Action $action) use ($voter) {
            $action
                ->setLabel('Liste')
                ->setIcon('fa fa-list')
                ->setHtmlAttributes(['title' => 'Retour à la liste'])
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
                ->setIcon('fa fa-pencil')
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
                ->setIcon('fa fa-list')
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
                ->setIcon('fa fa-list')
                ->setHtmlAttributes(['title' => 'Retour à la liste'])
                ->displayIf(function (AppEntityInterface $entity) use ($voter) {
                    return $this->isGranted($voter::ACTION_LIST, $entity);
                });
            return $action;
        });


        $actions
            ->add(Crud::PAGE_INDEX, $action_lnks['duplicate'])
            ->reorder(Crud::PAGE_INDEX, [Action::DELETE, 'duplicate', Action::EDIT, Action::DETAIL]);

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
        $shortname = Classes::getShortname(static::ENTITY, true);
        $crud
            ->setDefaultSort(static::DEFAULT_SORT)
            ->overrideTemplates([
                'crud/field/id' => '@EasyAdmin/crud/field/id_with_icon.html.twig',
                // 'crud/field/thumbnail' => '@EasyAdmin/crud/field/template.html.twig',
            ])
            ->setEntityLabelInSingular(Strings::singularize($shortname))
            ->setEntityLabelInPlural(Strings::pluralize($shortname))
            // ->hideNullValues()
            // ->setFormOptions([
            //     'attr' => ['class' => 'text-info']
            // ])
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
        // dump($context, $request->query->all(), $context->getCrud(), $context->getCrud()->getActionsConfig());
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

    protected function getContextInfo(): array
    {
        $info = [
            'user' => null,
            'entityDto' => null,
            'classname' => null,
            'entity' => null,
        ];
        $context = $this->getContext();
        if($context) {
            $info['user']       = $context->getUser() ?? $this->getUser();
            $info['entityDto']  = $context->getEntity();
            $info['classname']  = $info['entityDto'] instanceof EntityDto ? $info['entityDto']->getFqcn() : null;
            $info['entity']     = $info['entityDto'] instanceof EntityDto ? $info['entityDto']->getInstance() : null;
            if(empty($info['entity'])) {
                $info['entity'] = $this->createEntity(entityFqcn: $info['classname'], checkGrant: false);
                // $info['entity'] = $this->manager instanceof AppEntityManagerInterface
                //     ? $this->manager->getNew()
                //     : new $info['classname'];
            }
            $RC = new ReflectionClass($info['classname']);
            $info['instantiable'] = $RC->isInstantiable();
        }
        // dump($info);
        return $info;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $info = $this->getContextInfo();
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if(!$this->isGranted('ROLE_SUPER_ADMIN') && $info['entity'] instanceof EnabledInterface) {
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
        $info = $this->getContextInfo();
        $voter = $this->getEntityVoter();
        if($info['instantiable'] && is_a($voter, VoterInterface::class, true)) {
            switch ($pageName) {
                case Crud::PAGE_DETAIL:
                    if(!$this->isGranted(attribute: $voter::ACTION_READ, subject: $info['entity'])) {
                        $message = vsprintf('Vous n\'êtes pas autorisé consulter cet élément %s.', [$info['entity']?->__toString() ?? $info['entityDto']->getFqcn()]);
                        if($make_exception) throw new Exception(message: $message, code: 403);
                        $this->addFlash('danger', $message);
                        $opresult->addDanger($message);
                    }
                    break;
                case Crud::PAGE_NEW:
                    if(!$this->isGranted(attribute: $voter::ACTION_CREATE, subject: $info['entity'])) {
                        $message = vsprintf('Vous n\'êtes pas autorisé à réaliser cette création (%s).', [$info['entityDto']->getFqcn()]);
                        if($make_exception) throw new Exception(message: $message, code: 403);
                        $this->addFlash('danger', $message);
                        $opresult->addDanger($message);
                    }
                    break;
                case Crud::PAGE_EDIT:
                    if(!$this->isGranted(attribute: $voter::ACTION_UPDATE, subject: $info['entity'])) {
                        $message = vsprintf('Vous n\'êtes pas autorisé à réaliser cette modification de %s.', [$info['entity']?->__toString() ?? $info['entityDto']->getFqcn()]);
                        if($make_exception) throw new Exception(message: $message, code: 403);
                        $this->addFlash('danger', $message);
                        $opresult->addDanger($message);
                    }
                    break;
                default:
                if(!$this->isGranted(attribute: $voter::ACTION_LIST, subject: $info['entity'])) {
                    $message = vsprintf('Vous n\'êtes pas autorisé à réaliser cette opération "%s" sur l\'entité %s.', [$pageName, $info['entityDto']->getFqcn()]);
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
        $entity = $formBuilder->getData();
        if($entity instanceof AppEntityInterface) {
            $formBuilder->addEventSubscriber(new LaboFormsSubscriber($this->manager));
        }
        return $formBuilder;
    }

}