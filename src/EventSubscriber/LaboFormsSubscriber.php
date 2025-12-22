<?php
namespace Aequation\LaboBundle\EventSubscriber;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Aequation\LaboBundle\Model\Attribute\RelationOrder;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
// use Aequation\LaboBundle\Model\Interface\HasOrderedInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Tools\Classes;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Form;
use function Symfony\Component\String\u;

use Exception;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LaboFormsSubscriber implements EventSubscriberInterface
{

    // const TEST = false;

    public function __construct(
        protected AppEntityManagerInterface $appEntityManager,
    )
    {
        // if ($this->appEntityManager->isDev() && !in_array($this->appEntityManager::class, [AppEntityManager::class, ServiceAppEntityManager::class])) {
        //     throw new Exception(vsprintf('Error %s line %d: parameter is %s but %s required', [__METHOD__, __LINE__, $this->appEntityManager::class, ServiceAppEntityManager::class]));
        // }
    }

    /**
     * Get subscribed Events
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => ['preSetData'],
            FormEvents::POST_SET_DATA => ['postSetData'],
            FormEvents::PRE_SUBMIT => ['preSubmit'],
            FormEvents::SUBMIT => ['submit'],
            FormEvents::POST_SUBMIT => ['postSubmit'],
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $entity = $event->getData();
        if($entity instanceof AppEntityInterface) {
            $this->appEntityManager->dispatchEvent($entity, FormEvents::PRE_SET_DATA, ['event' => $event]);
        }
    }

    public function postSetData(FormEvent $event): void
    {
        /** @var Form $form */
        $form = $event->getForm();
        $entity = $form->getData();
        if($entity instanceof AppEntityInterface) {
            $this->appEntityManager->dispatchEvent($entity, FormEvents::POST_SET_DATA, ['event' => $event]);
        }
    }

    public function preSubmit(FormEvent $event): void
    {
        /** @var Form $form */
        $form = $event->getForm();
        $entity = $form->getData();
        if($entity instanceof AppEntityInterface) {
            $this->appEntityManager->dispatchEvent($entity, FormEvents::PRE_SUBMIT, ['event' => $event]);
        }
    }

    public function submit(FormEvent $event): void
    {
        /** @var Form $form */
        $form = $event->getForm();
        $entity = $form->getData();
        if($entity instanceof AppEntityInterface) {
            $this->appEntityManager->dispatchEvent($entity, FormEvents::SUBMIT, ['event' => $event]);
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        /** @var Form $form */
        $form = $event->getForm();
        $entity = $form->getData();
        if($entity instanceof AppEntityInterface) {
            $this->appEntityManager->dispatchEvent($entity, FormEvents::POST_SUBMIT, ['event' => $event]);
        }
    }

}