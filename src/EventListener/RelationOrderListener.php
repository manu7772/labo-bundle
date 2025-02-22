<?php
namespace Aequation\LaboBundle\EventListener;

use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\HasRelationOrderInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class RelationOrderListener
{
    public function __construct(
        private AppEntityManagerInterface $appem,
    )
    {
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        if($entity instanceof HasRelationOrderInterface) {
            
        }
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        /** @var AppEntityInterface */
        $entity = $event->getObject();
        if($entity instanceof HasRelationOrderInterface) {
            
        }
    }

}