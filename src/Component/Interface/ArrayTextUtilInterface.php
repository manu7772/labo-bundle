<?php
namespace Aequation\LaboBundle\Component\Interface;

use ArrayAccess;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use Traversable;

interface ArrayTextUtilInterface extends Stringable, Countable, ArrayAccess, IteratorAggregate, JsonSerializable
{
    public function __construct(array|string $strings = []);
    public function toArray(): array;
    public function setAll(array $strings): static;
    public function add(string $string): static;
    public function cleanup(bool $removeDuplicates = true): static;
    
    // Stringable
    public function __toString(): string;
    public function toString(string $separator = ','): string;
    // Countable
    public function count(): int;
    // ArrayAccess
    public function offsetExists(mixed $offset): bool;
    public function offsetGet(mixed $offset): mixed;
    public function offsetSet(mixed $offset, mixed $value): void;
    public function offsetUnset(mixed $offset): void;
    // IteratorAggregate
    public function getIterator(): Traversable;

    // JsonSerializable
    public function jsonSerialize(): string;
}