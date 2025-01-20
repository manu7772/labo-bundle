<?php
namespace Aequation\LaboBundle\EventListener;

use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Service\SlugService;
use Aequation\LaboBundle\Component\AppEntityInfo;
use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Final\FinalUserInterface;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Aequation\LaboBundle\Model\Interface\OwnerInterface;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\PreferedInterface;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\HasOrderedInterface;
use Aequation\LaboBundle\Service\Interface\PdfServiceInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
// App
use App\Entity\Entreprise;
use App\Entity\Category;
// Symfony
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
// PHP
use Exception;

#[AsDoctrineListener(event: AppEvent::onCreate)]
#[AsDoctrineListener(event: Events::postLoad, priority: 100)]
// #[AsDoctrineListener(event: AppEvent::onLoad)]
#[AsDoctrineListener(event: Events::prePersist, priority: 100)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::postUpdate)]
// #[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class GlobalDoctrineListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private SlugService $slugService,
        private AppRoleHierarchyInterface $roleHierarchy,
        private AppEntityManagerInterface $manager,
        private UserPasswordHasherInterface $userPasswordHasher,
        private ParameterBagInterface $parameterBag,
    ) {}

    // public function onLoad(LifecycleEventArgs $event): void
    // {
    //     /** @var AppEntityInterface */
    //     $entity = $event->getObject();
    //     if($entity instanceof HasOrderedInterface) $entity->loadedRelationOrder();
    // }

    public function onCreate(LifecycleEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        // dd($entity);
        // Specificity entity
        if($entity instanceof OwnerInterface) {
            $this->manager->defineEntityOwner($entity, false);
        }
        if($entity instanceof LaboUserInterface) {
            $entity->setRoleHierarchy($this->roleHierarchy);
        }
    }

    public function postLoad(PostLoadEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        // Add Entity manager
        $this->manager->setManagerToEntity($entity);
        // /** @var AppEntityManagerInterface */
        $entity->_service->dispatchEvent($entity, AppEvent::onLoad, ['event' => $event]);
        // Specificity entity
        if($entity instanceof LaboUserInterface) {
            $entity->setRoleHierarchy($this->roleHierarchy);
        }
        if($entity instanceof HasOrderedInterface) {
            $entity->loadedRelationOrder();
        }
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        // $entity->_service->dispatchEvent($entity, Events::prePersist);

        if($entity instanceof ImageInterface) {
            $entity->updateName();
        }

        if($entity instanceof PdfInterface) {
            if(empty($entity->getFile())) {
                // Create PDF file from content
                // dd($entity);
            }
        }

        // Specificity entity
        switch (true) {
            case $entity instanceof LaboUserInterface:
                $plainPassword = $entity->getPlainPassword();
                $hashed = $this->userPasswordHasher->hashPassword($entity, $plainPassword);
                $entity->setPassword($hashed);
                // On create roles
                $this->onCreateRoles(user: $entity);
                if(count($entity->getRoles()) > 1) $entity->setIsVerified(true);
                break;
        }

        // Uname
        if($entity instanceof UnamedInterface && empty($entity->getUname())) {
            $entity->autoUpdateUname();
            /** @var EntityManagerInterface $em */
            $em = $event->getObjectManager();
            /** @var Doctrine\ORM\UnitOfWork $uow */
            $uow = $em->getUnitOfWork();
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($entity::class), $entity);
        }

    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        $entity->_service->clearAppEvents(entity: $entity);
        // Specificity entity
        // switch (true) {
        //     case $entity instanceof SiteparamsInterface:
        //         $this->needRefresh ??= $entity->__getAppManaged()->manager;
        //         break;
        // }
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $entity_update = false;
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        // $entity->_service->dispatchEvent($entity, Events::preUpdate);

        // LaboUserInterface
        if($entity instanceof LaboUserInterface) {
            // Password
            $plainPassword = $entity->getPlainPassword();
            if(is_string($plainPassword) && !empty($plainPassword)) {
                $hashed = $this->userPasswordHasher->hashPassword($entity, $plainPassword);
                $entity->setPassword($hashed);
                $entity->updateUpdatedAt();
            }
            // User
            if($entity instanceof FinalUserInterface) {
                // Check mainentreprise
                // dd('--- '.($entity->isCheckMainentreprise() ? 'CHECK' : 'NO CHECK').' : mainentreprise is '.json_encode($entity->getMainentreprise()).' / computed is '.json_encode($entity->getComputedMainentreprise()), $entity);
                if($entity->isCheckMainentreprise()) {
                    /** @var ServiceEntityRepository */
                    $entrepriseRepository = $this->manager->getRepository(Entreprise::class);
                    $mainentreprise = $entrepriseRepository->findOneBy(['prefered' => true]);
                    /** @var ServiceEntityRepository */
                    $categoryRepository = $this->manager->getRepository(Category::class);
                    $maincategory = $categoryRepository->find(Category::ID_OF_MAIN_FOR_ENTREPRISE);
                    $computeChangeSet = false;
                    if($mainentreprise && $maincategory) {
                        if($entity->getMainentreprise()) {
                            if(!$entity->wasMainentreprise()) {
                                if($entity->isSoftdeleted()) {
                                    throw new Exception(vsprintf('Error line %d %s(): %s can not be updated when softdeleted!', [__LINE__, __METHOD__, $entity::class]));
                                }
                                if(!$this->manager->isUserGranted($entity, 'ROLE_ADMIN')) {
                                    $entity->addRole('ROLE_ADMIN');
                                }
                                $entity->setEnabled(true);
                                $entity->setIsVerified(true);
                                // Set mainentreprise
                                if(!$entity->getEntreprises()->contains($mainentreprise)) {
                                    $entity->addEntreprise($mainentreprise);
                                    $computeChangeSet = true;
                                }
                                // Set maincategory
                                if(!$entity->getCategorys()->contains($maincategory)) {
                                    $entity->addCategory($maincategory);
                                    $computeChangeSet = true;
                                }
                            }
                        } else {
                            if($entity->wasMainentreprise()) {
                                if($this->manager->isUserGranted($entity, 'ROLE_ADMIN')) {
                                    $entity->removeRole('ROLE_ADMIN');
                                }
                                // Unset mainentreprise
                                if($entity->getEntreprises()->contains($mainentreprise)) {
                                    $entity->removeEntreprise($mainentreprise);
                                    $computeChangeSet = true;
                                }
                                // Unset maincategory
                                if($entity->getCategorys()->contains($maincategory)) {
                                    $entity->removeCategory($maincategory);
                                    $computeChangeSet = true;
                                }
                            }
                        }
                        if($computeChangeSet) {
                            /** @var EntityManagerInterface $em */
                            $em = $event->getObjectManager();
                            $uow = $em->getUnitOfWork();
                            $uow->computeChangeSet($em->getClassMetadata($entity->getClassname()), $entity); // --> for Category
                            $uow->computeChangeSet($em->getClassMetadata($mainentreprise->getClassname()), $mainentreprise); // --> for Entreprise
                        }
                    } else if($this->manager->isDev()) {
                        // Prefered entreprise not found
                        throw new Exception(vsprintf('Error line %d %s(): %s can not be updated without prefered entreprise!', [__LINE__, __METHOD__, $entity::class]));
                    }
                }
            }
            // Verified
            if(count($entity->getRoles()) > 1) $entity->setIsVerified(true);
        }

        // Uname
        if($entity instanceof UnamedInterface && empty($entity->getUname())) {
            if($this->manager->isDev()) {
                throw new Exception(vsprintf('Error line %d %s(): %s can not be updated without uname!', [__LINE__, __METHOD__, $entity::class]));
            }
            $entity->autoUpdateUname();
            $entity_update = true;
        }

        if($entity_update) {
            /** @var EntityManagerInterface $em */
            $em = $event->getObjectManager();
            $uow = $em->getUnitOfWork();
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($entity::class), $entity);
        }

    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        $entity->_service->clearAppEvents(entity: $entity);
        // Specificity entity
        // switch (true) {
        //     case $entity instanceof SiteparamsInterface:
        //         $this->needRefresh ??= $entity->__getAppManaged()->manager;
        //         break;
        // }
    }

    // public function preRemove(PreRemoveEventArgs $event): void
    // {
    //     /** @var AppEntityInterface */
    //     // $entity = $event->getObject();
    // }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        // $entity->_service->clearAppEvents(entity: $entity);
        // Specificity entity
        // switch (true) {
        //     case $entity instanceof SiteparamsInterface:
        //         $this->needRefresh ??= $entity->__getAppManaged()->manager;
        //         break;
        // }
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        /** @var UnitOfWork */
        $uow = $this->em->getUnitOfWork();

        // SLUG ******************************************************************************************************************************************************
        // $resetSlugsControls = false;
        // Persist
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            switch (true) {
                case $entity instanceof SlugInterface:
                    if($this->slugService->computeUniqueSlug($entity)) {
                        $uow->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
                        // $resetSlugsControls = true;
                    }
                    break;
            }
            if($entity instanceof PdfInterface) {
                if(empty($entity->getFile())) {
                    // Create Pdf from content
                    $entity->setSourcetype('document');
                    if($entity->getFilename() === null) {
                        $entity->setFilename($entity->getSlug());
                        // $uow->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
                    }
                    // if($entity->getOriginalname() === null) {
                    //     $entity->setOriginalname($entity->getSlug());
                    //     $uow->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
                    // }
                    $entity->setOriginalname(null);
                    /** @var PdfServiceInterface */
                    $pdfService = $this->manager->getEntityService(Pdf::class);
                    $stream = $pdfService->output($entity);
                    $entity->setSize(mb_strlen($stream));
                    // $uow->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
                    $entity->setMime('application/pdf');
                    // $uow->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
                } else {
                    // Create Pdf from PDF file
                    $entity->setSourcetype('file');
                }
                $uow->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
            }
        }
        // Update
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            switch (true) {
                case $entity instanceof SlugInterface:
                    if($this->slugService->computeUniqueSlug($entity)) {
                        $uow->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
                        // $resetSlugsControls = true;
                    }
                    break;
            }
        }
        // Reset Slug controls if needed
        // if($resetSlugsControls) $this->slugService->resetControls();

        // PREFERED ******************************************************************************************************************************************************
        $preferedClasses = $this->manager->getEntityClassesOfInterface(PreferedInterface::class, false);

        foreach ($preferedClasses as $classname) {
            /** @var ServiceEntityRepositoryInterface $classRepo */
            $classRepo = $this->manager->getRepository($classname);
            $prefereds = $classRepo->findBy(['prefered' => true]);
            foreach ($prefereds as $entity) {
                /** @var PreferedInterface $entity */
                if(is_a($entity, $classname) && $entity instanceof EnabledInterface && !$entity->isActive()) {
                    $entity->setPrefered(false);
                    $uow->recomputeSingleEntityChangeSet($this->em->getMetadataFactory()->getMetadataFor($entity::class), $entity);
                }
            }
            $prefereds = new ArrayCollection(array_filter($prefereds, fn($ent) => $ent->isPrefered()));

            $active_one = null;
            foreach ($uow->getScheduledEntityInsertions() as $entity) {
                if(is_a($entity, $classname)) {
                    /** @var PreferedInterface $entity */
                    if($entity->isPrefered()) {
                        if($entity instanceof EnabledInterface && !$entity->isActive()) {
                            $entity->setPrefered(false);
                            $prefereds->removeElement($entity);
                            $uow->recomputeSingleEntityChangeSet($this->em->getMetadataFactory()->getMetadataFor($entity::class), $entity);
                        } else if($active_one && $entity->isPrefered()) {
                            $entity->setPrefered(false);
                            $prefereds->removeElement($entity);
                            $uow->recomputeSingleEntityChangeSet($this->em->getMetadataFactory()->getMetadataFor($entity::class), $entity);
                        } else if(!$active_one) {
                            $active_one = $entity;
                        }
                    }
                }
            }
            foreach ($uow->getScheduledEntityUpdates() as $entity) {
                if(is_a($entity, $classname)) {
                    /** @var PreferedInterface $entity */
                    if($entity->isPrefered()) {
                        if($entity instanceof EnabledInterface && !$entity->isActive()) {
                            $entity->setPrefered(false);
                            $prefereds->removeElement($entity);
                            $uow->recomputeSingleEntityChangeSet($this->em->getMetadataFactory()->getMetadataFor($entity::class), $entity);
                        } else if($active_one && $entity->isPrefered()) {
                            $entity->setPrefered(false);
                            $prefereds->removeElement($entity);
                            $uow->recomputeSingleEntityChangeSet($this->em->getMetadataFactory()->getMetadataFor($entity::class), $entity);
                        } else if(!$active_one) {
                            $active_one = $entity;
                        }
                    }
                }
            }
            foreach ($uow->getScheduledEntityDeletions() as $entity) {
                if(is_a($entity, $classname) && $entity->isPrefered() && !$active_one) {
                    /** @var PreferedInterface $entity */
                    // throw new Exception(vsprintf('Error line %d %s(): can not remove %s when prefered!', [__LINE__, __METHOD__, $entity->getClassname()]));
                }
            }
            if($active_one) {
                foreach ($prefereds as $entity) {
                    if($entity !== $active_one) {
                        $entity->setPrefered(false);
                        $uow->recomputeSingleEntityChangeSet($this->em->getMetadataFactory()->getMetadataFor($entity::class), $entity);
                    }
                }
            } else if($prefereds->isEmpty()) {
                $done = false;
                do {
                    $one = is_a($classname, EnabledInterface::class, true)
                        ? $classRepo->findOneBy(['enabled' => true, 'softdeleted' => false])
                        : $classRepo->findOneBy(['prefered' => false]);
                    if($one) {
                        if(!$uow->isScheduledForDelete($one)) {
                            $one->setPrefered(true);
                            $uow->recomputeSingleEntityChangeSet($this->em->getMetadataFactory()->getMetadataFor($one::class), $one);
                            $done = true;
                        }
                    }
                } while ($done);
            }
        }

        // Softdelete
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if($entity instanceof EnabledInterface && !$entity->isSoftdeleted()) {
                throw new Exception(vsprintf('Error line %d %s(): %s can not be deleted!', [__LINE__, __METHOD__, $entity::class]));
                // if(!$entity->isSoftdeleted() || !$this->manager->isGranted('ROLE_SUPER_ADMIN')) {
                    // $id = $entity->getId();
                    // $classname = $entity->getClassname();
                    // detach entity
                    // $uow->detach($entity);
                    // retrieve entity
                    // $entity = $this->manager->getRepository($classname)->find($id);
                    // $entity->setSoftdeleted(true);
                    // $uow->scheduleForUpdate($entity);
                    // $uow->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
                    // if($uow->isScheduledForDelete($entity)) {
                    //     throw new Exception(vsprintf('Error line %d %s(): %s can not be deleted!', [__LINE__, __METHOD__, $entity::class]));
                    // }
                    // if(!$uow->isScheduledForUpdate($entity)) {
                    //     throw new Exception(vsprintf('Error line %d %s(): %s can not be softdeleted!', [__LINE__, __METHOD__, $entity::class]));
                    // }
                // }
            }
        }

    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->slugService->resetControls();
    }




    protected function onCreateRoles(LaboUserInterface $user): void
    {
        /** @var User $user */
        $roles = $this->parameterBag->has('on_create_roles') ? $this->parameterBag->get('on_create_roles') : [];
        foreach ($roles as $email => $role) {
            if($user->getEmail() === $email) {
                $user->addRole($role);
                break;
            }
        }

    }

    // public function recomputeChangeSet(LifecycleEventArgs $event): void
    // {
    //     $object = $event->getObject();
    //     /** @var EntityManagerInterface $em */
    //     $em = $event->getObjectManager();
    //     $uow = $em->getUnitOfWork();
    //     $metadata = $em->getClassMetadata($object::class);
    //     $uow->recomputeSingleEntityChangeSet($metadata, $object);
    // }

}
