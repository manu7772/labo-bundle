<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Closure;
use Countable;
use Exception;
use Iterator;

class HydratedReferences implements Countable, Iterator
{

    protected ArrayCollection $hydrated_references;
    protected mixed $current;

    public function __construct(
        Iterable $entities = [],
    )
    {
        $this->hydrated_references = new ArrayCollection();
        foreach ($entities as $entity) {
            $this->add($entity);
        }
        $this->rewind();
    }

    public function add(
        AppEntityInterface $entity,
    ): static
    {
        // $test = $this->hydrated_references->indexOf($entity);
        // if($test) {
        //     // Already here
        //     if($test !== $entity->getUnameThenEuid()) throw new Exception(vsprintf('Error %s line %d: %s contains this entity %s (uname: %s) but with wrond reference: %s!', [__METHOD__, __LINE__, static::class, $entity->getClassname(), $entity->getUnameThenEuid(), $test]));
        // }
        $this->hydrated_references->set($entity->getUnameThenEuid(), $entity);
        $this->rewind();
        return $this;
    }

    public function getByClassname(
        string $classname,
        bool $strictClass = false,
    ): ArrayCollection
    {
        return $this->getFiltered(function($entity) use ($classname, $strictClass) {
            return $strictClass
                ? $entity->getClassname() === $classname
                : is_a($entity, $classname);
        });
    }

    public function get(
        string $reference,
    ): ?AppEntityInterface
    {
        return $this->hydrated_references->get($reference);
    }

    public function getAllReferences(): array
    {
        return $this->hydrated_references->getKeys();
    }

    public function getNotPersisteds(
        ?string $classname = null,
        bool $strictClass = false,
    ): ArrayCollection
    {
        return $this->getFiltered(function($entity) use ($classname, $strictClass) {
            $is = true;
            if(!empty($classname)) {
                $is = $strictClass
                    ? $entity->getClassname() === $classname
                    : is_a($entity, $classname);
            }
            return $is && $entity->_appManaged->isNew();
        });
    }

    public function getOnlyPersisteds(
        ?string $classname = null,
        bool $strictClass = false,
    ): ArrayCollection
    {
        return $this->getFiltered(function($entity) use ($classname, $strictClass) {
            $is = true;
            if(!empty($classname)) {
                $is = $strictClass
                    ? $entity->getClassname() === $classname
                    : is_a($entity, $classname);
            }
            return $is && $entity->_appManaged->isPersisted();
        });
    }

    public function remove(
        AppEntityInterface|string $entityOrRef
    ): bool
    {
        $method = is_string($entityOrRef) ? 'remove' : 'removeElement';
        $result = $this->hydrated_references->$method($entityOrRef);
        $this->rewind();
        return $result;
    }

    public function clear(
        null|string|array $classnames = null
    ): static
    {
        $classnames = empty($classnames) ? [] : (array)$classnames;
        if(empty($classnames)) {
            $this->hydrated_references->clear();
        } else {
            $this->filter(function($entity) use ($classnames) {
                return in_array($entity->getClassname(), $classnames);
            });
        }
        $this->rewind();
        return $this;
    }

    public function toArray(): array
    {
        return $this->hydrated_references->toArray();
    }

    public function count(): int
    {
        return $this->hydrated_references->count();
    }

    public function current(): mixed
    {
        return $this->current;
    }

    public function first(): ?AppEntityInterface
    {
        $this->rewind();
        return $this->current;
    }

    public function key(): mixed
    {
        return $this->current instanceof AppEntityInterface
            ? $this->current->getUnameThenEuid()
            : null;
    }

    public function next(): void
    {
        $this->current = $this->hydrated_references->next();
    }

    public function rewind(): void
    {
        $this->current = $this->hydrated_references->first();
    }

    public function valid(): bool
    {
        return true;
    }

    public function filter(Closure $callback): static
    {
        $this->hydrated_references = $this->getFiltered($callback);
        $this->rewind();
        return $this;
    }

    public function getFiltered(Closure $callback): ArrayCollection
    {
        return $this->hydrated_references->filter($callback);
    }

    public function isEmpty(): bool
    {
        return $this->hydrated_references->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->hydrated_references->count() > 0;
    }

    public function findFirst(Closure $callback): ?AppEntityInterface
    {
        return $this->hydrated_references->findFirst($callback);
    }

    public function hasClassname(
        string $classname
    ): bool
    {
        return $this->getByClassname($classname)->count() > 0;
    }

    public function contains(
        AppEntityInterface $entity
    ): bool
    {
        return $this->hydrated_references->contains($entity);
    }

}