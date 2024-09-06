<?php
namespace Aequation\LaboBundle\EventListener\Attribute;

use Aequation\LaboBundle\Model\Attribute\baseClassAttribute;
use Aequation\LaboBundle\Model\Interface\AppAttributeMethodInterface;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\AppEventInterface;
use Attribute;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Form\FormEvents;

/**
 * Methods before Validate entity
 * @Target({"METHOD"})
 * @author emmanuel:dujardin Aequation
 * Attribute
 * @see https://www.php.net/manual/fr/language.attributes.classes.php
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class AppEvent extends baseClassAttribute implements AppEventInterface, AppAttributeMethodInterface
{
    // public const DEFAULT            = 'default';
    public const onCreate           = 'onCreate';
    public const onLoad             = 'onLoad';
    public const beforeClone        = 'beforeClone';
    public const afterClone         = 'afterClone';
    public const beforePrePersist   = 'beforePrePersist';
    public const beforePreUpdate    = 'beforePreUpdate';
    public const beforePreRemove    = 'beforePreRemove';
    // Form events
    public const POST_SUBMIT        = FormEvents::POST_SUBMIT;
    public const PRE_SET_DATA       = FormEvents::PRE_SET_DATA;

    public readonly ReflectionMethod $method;
    public readonly array $events;


    public function __construct(
        public array|string|null $groups,
    ) {
        $this->groups = $this->toArrayGroups($groups);
    }

    public function setMethod(ReflectionMethod|string $method): static
    {
        $this->method = is_string($method) ? new ReflectionMethod($method) : $method;
        return $this;
    }

    public function hasGroup(string $group): bool
    {
        return in_array($group, $this->groups);
    }

    public function isApplicable(
        AppEntityInterface $entity,
        string $group
    ): bool
    {
        if($entity->_appManaged->isPersisted()) {
            $newevents = static::getNewEvents();
            if(in_array($group, $newevents)) return false;
        } else {
            $newevents = static::getPersistedEvents();
            if(in_array($group, $newevents)) return false;
        }
        return !$entity->_appManaged->isAppEventApplicable($group) && in_array($group, $this->groups);
    }

    public static function getNewEvents(): array
    {
        return [static::onCreate , static::beforePrePersist];
    }

    public static function getPersistedEvents(): array
    {
        return [static::onLoad , static::beforePreUpdate];
    }

    public static function getEvents(): array
    {
        $rc = new ReflectionClass(static::class);
        return $rc->getConstants();
    }

    public static function hasEvent(string $event): bool
    {
        $events = static::getEvents();
        return in_array($event, $events);
    }

    private function toArrayGroups(array|string|null &$groups = null): array
    {
        if(!is_array($groups)) {
            $groups = empty($groups)
                ? []
                : [$groups]
                ;
        }
        return $groups = array_unique(array_values($groups));
        // if(empty($groups)) $groups = [static::DEFAULT];
    }

    public function __serialize(): array
    {
        $parent = parent::__serialize();
        $data = [
            'method' => $this->method->name,
            'events' => $this->events,
        ];
        return array_merge($parent, $data);
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->setMethod($data['method']);
        $this->events = $data['events'];
    }

}