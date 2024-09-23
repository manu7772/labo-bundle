<?php
namespace Aequation\LaboBundle\EventListener;

use Aequation\LaboBundle\Component\AppEntityInfo;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
use Aequation\LaboBundle\Model\Interface\HasOrderedInterface;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Aequation\LaboBundle\Model\Interface\OwnerInterface;
use Aequation\LaboBundle\Model\Interface\PreferedInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
use Aequation\LaboBundle\Service\SlugService;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
// use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: AppEvent::onCreate)]
#[AsDoctrineListener(event: Events::postLoad, priority: 100)]
// #[AsDoctrineListener(event: AppEvent::onLoad)]
#[AsDoctrineListener(event: Events::prePersist)]
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
        dd($entity);
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
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        // $entity->_service->dispatchEvent($entity, Events::prePersist);

        if($entity instanceof ImageInterface) {
            $entity->updateName();
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
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        // $entity->_service->dispatchEvent($entity, Events::preUpdate);

        // Specificity entity
        switch (true) {
            case $entity instanceof LaboUserInterface:
                $plainPassword = $entity->getPlainPassword();
                if(is_string($plainPassword) && !empty($plainPassword)) {
                    $hashed = $this->userPasswordHasher->hashPassword($entity, $plainPassword);
                    $entity->setPassword($hashed);
                    $entity->updateUpdatedAt();
                }
                if(count($entity->getRoles()) > 1) $entity->setIsVerified(true);
                break;
        }

        // Uname
        if($entity instanceof UnamedInterface && empty($entity->getUname())) {
            $entity->autoUpdateUname();
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
