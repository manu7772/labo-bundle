<?php
namespace Aequation\LaboBundle\Component\Interface;

// Symfony
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Collections\Criteria;
// PHP
use Closure;
use Stringable;
use Traversable;

interface TypedCollectionInterface extends Collection, Selectable, Stringable
{
    // public function __construct(array $elements = [])
    public function toArray(): array;
    public function first(): mixed;
    public function last(): mixed;
    public function key(): int|string|null;
    public function next(): mixed;
    public function current(): mixed;
    public function remove(string|int $key): mixed;
    public function removeElement(mixed $element): bool;
    public function offsetExists(mixed $offset): bool;
    public function offsetGet(mixed $offset): mixed;
    public function offsetSet(mixed $offset, mixed $value): void;
    public function offsetUnset(mixed $offset): void;
    public function containsKey(string|int $key): bool;
    public function contains(mixed $element): bool;
    public function exists(Closure $p): bool;
    public function indexOf($element): int|string|false;
    public function get(string|int $key): mixed;
    public function getKeys(): array;
    public function getValues(): array;
    public function count(): int;
    public function set(string|int $key, mixed $value): void;
    public function add(mixed $element): void;
    public function isEmpty(): bool;
    public function getIterator(): Traversable;
    public function map(Closure $func): TypedCollectionInterface;
    public function reduce(Closure $func, $initial = null): mixed;
    public function filter(Closure $p): TypedCollectionInterface;
    public function findFirst(Closure $p): mixed;
    public function forAll(Closure $p): bool;
    public function partition(Closure $p): array;
    public function __toString(): string;
    public function clear(): void;
    public function slice(int $offset, int|null $length = null): array;
    public function mapSingleValue(string $field): array;
    public function mapValues(array $fields): array;
    public function sortBy(string $property, bool $asc = true): TypedCollectionInterface;
    public function sortFn(Closure $callback): TypedCollectionInterface;
    public function matching(Criteria $criteria): TypedCollectionInterface;
}