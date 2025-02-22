<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\AppEntityInfoInterface;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Aequation\LaboBundle\Repository\Interface\CommonReposInterface;
use Aequation\LaboBundle\Service\AppService;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use BadMethodCallException;
use Exception;

class AppEntityInfo implements AppEntityInfoInterface
{

    protected array $internals = [];
    protected array $appEvents = [];

    public function __construct(
        public readonly AppEntityInterface $entity,
        public readonly AppEntityManagerInterface $manager,
        // array|string|null $applyEvents = null,
    ) {
        $this->internals['entity'] = $this->entity->getEuid();
        $this->internals['manager'] = get_class($this->manager);
        $this->entity->__setAppManaged($this);
        // if(!empty($applyEvents)) {
        //     foreach ((array)$applyEvents as $event) {
        //         $this->manager->dispatchEvent($this->entity, $event);
        //     }
        // } else {
        //     /**
        //      * Auto Event:
        //      * - AppEvent::onCreate if new entity
        //      * - AppEvent::onLoad if loaded from database
        //      */
        //     if($this->isPersisted()) {
        //         $this->manager->dispatchEvent($this->entity, AppEvent::onLoad);
        //     } else {
        //         $this->manager->dispatchEvent($this->entity, AppEvent::onCreate);
        //     }
        // }
    }

    public function isPersisted(): bool
    {
        return !empty($this->entity->getId());
    }

    public function isNew(): bool
    {
        return empty($this->entity->getId());
    }

    public function __sleep(): array
    {
        throw new BadMethodCallException(vsprintf('Cannot serialize %s', [static::class.(static::class !== __CLASS__ ? PHP_EOL.'(based on '.__CLASS__.')' : '')]));
    }

    public function __wakeup(): void
    {
        throw new BadMethodCallException(vsprintf('Cannot unserialize %s', [static::class.(static::class !== __CLASS__ ? PHP_EOL.'(based on '.__CLASS__.')' : '')]));
    }

    public function isValid(): bool
    {
        $serviceName = AppService::getClassServiceName($this->entity);
        try {
            $this->manager instanceof $serviceName;
        } catch (\Throwable $th) {
            //throw $th;
            throw new Exception(vsprintf('Error on %s line %d: %s', [__METHOD__, __LINE__, $th->getMessage()]));
            // dd($this->entity, $serviceName, $th->getMessage());
        }
        return
            $this->entity instanceof AppEntityInterface
            && $this->entity->_appManaged === $this
            && $this->manager instanceof $serviceName
            && $this->entity->_service === $this->manager
            && $this->manager instanceof AppEntityManagerInterface
            ;
    }

    public function getRepository(): CommonReposInterface
    {
        return $this->manager->getRepository($this->entity->getClassname());
    }

    public function getPropertysAttributes(string $attribute): array
    {
        return Classes::getPropertyAttributes($this->entity, $attribute);
    }

    public function getManager(): AppEntityManagerInterface
    {
        return $this->manager;
    }

    /*********************************************************************************************************/
    /** AppEvents                                                                                            */
    /*********************************************************************************************************/

    public function clearAppEvents(): static
    {
        foreach (array_keys($this->appEvents) as $group) {
            $this->appEvents[$group] = false;
        }
        return $this;
    }

    public function setAppEventApplyed(
        string $group,
        bool $applyed = true
    ): static
    {
        $this->appEvents[$group] = $applyed;
        return $this;
    }

    public function isAppEventApplicable(
        string $group
    ): bool
    {
        return $this->appEvents[$group] ?? false;
    }


    public function __call($name, $arguments)
    {
        $internal_name = $this->getValueName($name);
        switch (true) {
            case preg_match('/^get\w+/', $name) && (isset($this->internals[$internal_name]) || count($arguments) > 0):
                return $this->internals[$internal_name] ?? empty($arguments) ? null : reset($arguments);
                break;
            case preg_match('/^is\w+/', $name) && (isset($this->internals[$internal_name]) || count($arguments) > 0):
                $value = $this->internals[$internal_name] ?? empty($arguments) ? false : reset($arguments);
                return (bool)$value;
                break;
            case preg_match('/^has\w+/', $name) && (isset($this->internals[$internal_name]) || count($arguments) > 0):
                $value = $this->internals[$internal_name] ?? empty($arguments) ? null : reset($arguments);
                return !empty($value);
                break;
            case preg_match('/^set\w+/', $name):
                if(empty($arguments)) throw new Exception(vsprintf('Error on %s line %d: setter "%s" needs at least one argument.', [__METHOD__, __LINE__, $name]));
                // dump(vsprintf('Info on %s line %d: with name "%s", updated/created new value "%s" with following arguements.', [__METHOD__, __LINE__, $name, $internal_name]), $arguments);
                $this->internals[$internal_name] = reset($arguments);
                break;
            default:
                throw new Exception(vsprintf('Error on %s line %d: can not call this method "%s" because it does not exist.', [__METHOD__, __LINE__, $name]));
                break;
        }
    }

    public function __isset(string $name)
    {
        return isset($this->internals[$name]);
    }

    private function getValueName(string $name): string
    {
        $name = ucfirst(preg_replace('/^(get|set|is|has)*/i', '', $name));
        if(empty($name)) throw new Exception(vsprintf('Error on %s line %d: name generated with base "%s" is invalid.', [__METHOD__, __LINE__, $name]));
        return $name;
    }

    /**
     * Get data for serialize
     * @return array
     */
    public function __serialize(): array
    {
        return $this->internals;
    }
    
    /**
     * Set properties from data
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $name => $value) {
            $this->internals[$name] = $value;
        }
    }

    /**
     * Get serialize data
     * @return array
     */
    public function serialize(): ?string
    {
        return json_encode($this->__serialize());
    }
    
    /**
     * Set data from (string) data
     * @param string $data
     * @return void
     */
    public function unserialize(string $data): void
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

    public function setRelationOrderLoaded(
        bool $loaded
    ): void
    {
        if(!($this->entity instanceof EcollectionInterface)) {
            throw new Exception(vsprintf('Error on %s line %d: entity is not an instance of %s.', [__METHOD__, __LINE__, EcollectionInterface::class]));
        }
        $this->internals['RelationOrder'] = $loaded;
        dump(vsprintf('Info on %s line %d: RelationOrder is set to %s.', [__METHOD__, __LINE__, json_encode($loaded)]));
    }

    public function isRelationOrderLoaded(
        bool $default = false
    ): bool
    {
        if(!($this->entity instanceof EcollectionInterface)) {
            throw new Exception(vsprintf('Error on %s line %d: entity is not an instance of %s.', [__METHOD__, __LINE__, EcollectionInterface::class]));
        }
        return isset($this->internals['RelationOrder']) ? $this->internals['RelationOrder'] : $default;
    }

}