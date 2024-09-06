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
use Aequation\LaboBundle\Model\Interface\HasOrderedInterface;
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

    const TEST = false;

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
            if(static::TEST && $entity instanceof HasOrderedInterface && $this->appEntityManager->isDev()) {
                dump(__METHOD__, $event->getForm(), $entity);
            }
            $this->appEntityManager->dispatchEvent($entity, FormEvents::PRE_SET_DATA, ['event' => $event]);
        }
    }

    public function postSetData(FormEvent $event): void
    {
        $entity = $event->getForm()->getData();
        if($entity instanceof AppEntityInterface) {
            if($entity instanceof HasOrderedInterface && static::TEST && $this->appEntityManager->isDev()) {
                dump(__METHOD__, $event->getForm(), $entity);
                // dump(__METHOD__, $event->getForm(), $event->getForm()->all()['items']->getConfig()->getAttribute('choice_list'));
            }
            $this->appEntityManager->dispatchEvent($entity, FormEvents::POST_SET_DATA, ['event' => $event]);
        }
    }

    public function preSubmit(FormEvent $event): void
    {
        /** @var Form $form */
        $form = $event->getForm();
        $entity = $form->getData();
        if($entity instanceof AppEntityInterface) {
            if(!$entity->__isAppManaged()) $this->appEntityManager->setManagerToEntity($entity);
            if($entity instanceof HasOrderedInterface) {
                $attributes = Classes::getPropertysAttributes($entity, RelationOrder::class);
                if(empty($attributes)) throw new Exception(vsprintf('Error %s line %d: no field found for %s in entity %s!', [__METHOD__, __LINE__, RelationOrder::class, $entity->getClassname()]));
                $form_fields = $form->all();
                foreach ($attributes as $attr) {
                    $attr = reset($attr);
                    $names = $attr->property->name;
                    $method = u('remove_'.$names)->camel()->__toString();
                    if(array_key_exists($names, $form_fields) && !$form_fields[$names]->getConfig()->getOption('disabled')) {
                        // Need to empty field's Collection to ensure reordering
                        if(method_exists($entity, $method)) {
                            $entity->$method();
                        } else {
                            // $method = 'remove'.ucfirst(preg_replace('/s$/', '', $names));
                            throw new Exception(vsprintf('Error %s line %d: method %s() required for entity %s with %s attribute!', [__METHOD__, __LINE__, $method, $entity->getClassname(), RelationOrder::class]));
                        }
                    } else {
                        // dd($entity);
                    }
                }
                // if($event->getForm()->getData() instanceof AppEntityInterface) {
                    if(static::TEST && $this->appEntityManager->isDev()) {
                        dump(__METHOD__, $form, $entity->getShortname().' > '.$entity->__toString());
                    }
                    $this->appEntityManager->dispatchEvent($entity, FormEvents::PRE_SUBMIT, ['event' => $event]);
                // }
            }
        }
    }

    public function submit(FormEvent $event): void
    {
        $entity = $event->getForm()->getData();
        if($entity instanceof AppEntityInterface) {
            if(!$entity->__isAppManaged()) $this->appEntityManager->setManagerToEntity($entity);
            if($entity instanceof HasOrderedInterface) {
                if(static::TEST && $this->appEntityManager->isDev()) {
                    dump(__METHOD__, $event->getForm(), $entity->getShortname().' > '.$entity->__toString());
                }
            }
            $this->appEntityManager->dispatchEvent($entity, FormEvents::SUBMIT, ['event' => $event]);
        }
    }

    public function postSubmit(FormEvent $event): void
    {
        $entity = $event->getForm()->getData();
        if($entity instanceof AppEntityInterface) {
            if(!$entity->__isAppManaged()) $this->appEntityManager->setManagerToEntity($entity);
            if($entity instanceof HasOrderedInterface) {
                if(static::TEST && $this->appEntityManager->isDev()) {
                    dump(__METHOD__, $event->getForm(), $entity->getShortname().' > '.$entity->__toString());
                }
            }
            $this->appEntityManager->dispatchEvent($entity, FormEvents::POST_SUBMIT, ['event' => $event]);
        }
    }

}