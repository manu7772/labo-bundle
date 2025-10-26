<?php
namespace Aequation\LaboBundle\Component;

// Symfony
use Closure;
use ArrayAccess;
use Traversable;
use function end;
use function key;
use ArrayIterator;
use function next;
// PHP
use function count;
use function reset;
use function uasort;
use function current;

use function in_array;
use function array_all;
use function array_any;
use function array_map;
use function array_find;
use function array_keys;
use function array_slice;
use function array_filter;
use function array_reduce;
use function array_search;
use function array_values;
use function array_reverse;
use function spl_object_hash;
use function array_key_exists;
use const ARRAY_FILTER_USE_BOTH;
use Doctrine\Common\Collections\Order;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Aequation\LaboBundle\Component\Interface\TypedCollectionInterface;

/**
 * An TypedCollection is a Collection implementation that wraps a regular PHP array with ojbects.
 *
 * Warning: Using (un-)serialize() on a collection is not a supported use-case
 * and may break when we change the internals in the future. If you need to
 * serialize a collection use {@link toArray()} and reconstruct the collection
 * manually.
 *
 * @phpstan-template TKey of array-key
 * @phpstan-template T
 * @template-implements TypedCollection<TKey,T>
 * @template-implements Selectable<TKey,T>
 * @phpstan-consistent-constructor
 */
abstract class TypedCollection implements TypedCollectionInterface
{
    /**
     * An array containing the entries of this collection.
     *
     * @phpstan-var array<TKey,T>
     * @var mixed[]
     */
    protected array $elements = [];
    public readonly PropertyAccessorInterface $accessor;

    /**
     * Initializes a new TypedCollection.
     *
     * @phpstan-param array<TKey,T> $elements
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    protected function getAccessor(): PropertyAccessorInterface
    {
        return $this->accessor ??= PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidPropertyPath()->getPropertyAccessor();
    }

    public function toArray(): array
    {
        return $this->elements;
    }

    public function first(): mixed
    {
        return reset($this->elements);
    }

    /**
     * Creates a new instance from the specified elements.
     *
     * This method is provided for derived classes to specify how a new
     * instance should be created when constructor semantics have changed.
     *
     * @param array $elements Elements.
     * @phpstan-param array<K,V> $elements
     *
     * @return static
     * @phpstan-return static<K,V>
     *
     * @phpstan-template K of array-key
     * @phpstan-template V
     */
    protected function createFrom(array $elements): TypedCollectionInterface
    {
        return new static($elements);
    }

    public function last(): mixed
    {
        return end($this->elements);
    }

    public function key(): int|string|null
    {
        return key($this->elements);
    }

    public function next(): mixed
    {
        return next($this->elements);
    }

    public function current(): mixed
    {
        return current($this->elements);
    }

    public function remove(string|int $key): mixed
    {
        if (! isset($this->elements[$key]) && ! array_key_exists($key, $this->elements)) {
            return null;
        }

        $removed = $this->elements[$key];
        unset($this->elements[$key]);

        return $removed;
    }

    public function removeElement(mixed $element): bool
    {
        $key = array_search($element, $this->elements, true);

        if ($key === false) {
            return false;
        }

        unset($this->elements[$key]);

        return true;
    }

    /**
     * Required by interface ArrayAccess.
     *
     * @param TKey $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->containsKey($offset);
    }

    /**
     * Required by interface ArrayAccess.
     *
     * @param TKey $offset
     *
     * @return T|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Required by interface ArrayAccess.
     *
     * @param TKey|null $offset
     * @param T         $value
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);

            return;
        }

        $this->set($offset, $value);
    }

    /**
     * Required by interface ArrayAccess.
     *
     * @param TKey $offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    public function containsKey(string|int $key): bool
    {
        return isset($this->elements[$key]) || array_key_exists($key, $this->elements);
    }

    public function contains(mixed $element): bool
    {
        return in_array($element, $this->elements, true);
    }

    public function exists(Closure $p): bool
    {
        return array_any(
            $this->elements,
            static fn (mixed $element, mixed $key): bool => (bool) $p($key, $element),
        );
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param TMaybeContained $element
     *
     * @return int|string|false
     * @phpstan-return (TMaybeContained is T ? TKey|false : false)
     *
     * @template TMaybeContained
     */
    public function indexOf($element): int|string|false
    {
        return array_search($element, $this->elements, true);
    }

    public function get(string|int $key): mixed
    {
        return $this->elements[$key] ?? null;
    }

    public function getKeys(): array
    {
        return array_keys($this->elements);
    }

    public function getValues(): array
    {
        return array_values($this->elements);
    }

    /**
     * {@inheritDoc}
     *
     * @return int<0, max>
     */
    public function count(): int
    {
        return count($this->elements);
    }

    public function set(string|int $key, mixed $value): void
    {
        $this->elements[$key] = $value;
    }

    /**
     * {@inheritDoc}
     *
     * This breaks assumptions about the template type, but it would
     * be a backwards-incompatible change to remove this method
     */
    public function add(mixed $element): void
    {
        $this->elements[] = $element;
    }

    public function isEmpty(): bool
    {
        return empty($this->elements);
    }

    /**
     * {@inheritDoc}
     *
     * @return Traversable<int|string, mixed>
     * @phpstan-return Traversable<TKey, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param Closure(T):U $func
     *
     * @return static
     * @phpstan-return static<TKey, U>
     *
     * @phpstan-template U
     */
    public function map(Closure $func): TypedCollectionInterface
    {
        return $this->createFrom(array_map($func, $this->elements));
    }

    public function reduce(Closure $func, $initial = null): mixed
    {
        return array_reduce($this->elements, $func, $initial);
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-param Closure(T, TKey):bool $p
     *
     * @return static
     * @phpstan-return static<TKey,T>
     */
    public function filter(Closure $p): TypedCollectionInterface
    {
        return $this->createFrom(array_filter($this->elements, $p, ARRAY_FILTER_USE_BOTH));
    }

    public function findFirst(Closure $p): mixed
    {
        return array_find(
            $this->elements,
            static fn (mixed $element, mixed $key): bool => (bool) $p($key, $element),
        );
    }

    public function forAll(Closure $p): bool
    {
        return array_all(
            $this->elements,
            static fn (mixed $element, mixed $key): bool => (bool) $p($key, $element),
        );
    }

    public function partition(Closure $p): array
    {
        $matches = $noMatches = [];

        foreach ($this->elements as $key => $element) {
            if ($p($key, $element)) {
                $matches[$key] = $element;
            } else {
                $noMatches[$key] = $element;
            }
        }

        return [$this->createFrom($matches), $this->createFrom($noMatches)];
    }

    /**
     * Returns a string representation of this object.
     * {@inheritDoc}
     *
     * @return string
     */
    public function __toString(): string
    {
        return self::class.'@'.spl_object_hash($this);
    }

    public function clear(): void
    {
        $this->elements = [];
    }

    public function slice(int $offset, int|null $length = null): array
    {
        return array_slice($this->elements, $offset, $length, true);
    }

    public function mapSingleValue(string $field): array
    {
        return array_map(fn (object $wCmd) => $this->getAccessor()->getValue($wCmd, $field), $this->toArray());
    }

    public function mapValues(array $fields): array
    {
        return array_map(
            function (object $wCmd) use ($fields) {
                $values = [];
                foreach ($fields as $field) {
                    $values[$field] = $this->getAccessor()->getValue($wCmd, $field);
                }
                return $values;
            },
            $this->toArray()
        );
    }

    public function sortBy(string $property, bool $asc = true): TypedCollectionInterface
    {
        usort($this->elements, function ($a, $b) use ($property, $asc) {
            $aValue = $asc ? $this->getAccessor()->getValue($b, $property) : $this->getAccessor()->getValue($a, $property);
            $bValue = $asc ? $this->getAccessor()->getValue($a, $property) : $this->getAccessor()->getValue($b, $property);
            return is_string($aValue)
                ? strcmp((string) $aValue, (string) $bValue) // String comparison
                : $aValue <=> $bValue; // Numeric comparison
        });
        return $this;
    }

    public function sortFn(Closure $callback): TypedCollectionInterface
    {
        usort($this->elements, $callback);
        return $this;
    }

    /** @phpstan-return Collection<TKey, T>&Selectable<TKey,T> */
    public function matching(Criteria $criteria): TypedCollectionInterface
    {
        $expr     = $criteria->getWhereExpression();
        $filtered = $this->elements;

        if ($expr) {
            $visitor  = new ClosureExpressionVisitor();
            $filter   = $visitor->dispatch($expr);
            $filtered = array_filter($filtered, $filter);
        }

        $orderings = $criteria->orderings();

        if ($orderings) {
            $next = null;
            foreach (array_reverse($orderings) as $field => $ordering) {
                $next = ClosureExpressionVisitor::sortByField($field, $ordering === Order::Descending ? -1 : 1, $next);
            }

            uasort($filtered, $next);
        }

        $offset = $criteria->getFirstResult();
        $length = $criteria->getMaxResults();

        if ($offset !== null && $offset > 0 || $length !== null && $length > 0) {
            $filtered = array_slice($filtered, (int) $offset, $length, true);
        }

        return $this->createFrom($filtered);
    }
}
