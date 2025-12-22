<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\ArrayTextUtilInterface;
// Symfony
use Traversable;
// PHP
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class ArrayTextUtil
 * @package Aequation\LaboBundle\Component
 * 
 * Manages an array of strings
 *
 * @author Aequation
 */
class ArrayTextUtil implements ArrayTextUtilInterface
{

    /**
     * @var array
     */
    protected array $strings = [];

    public function __construct(
        array|string $strings = []
    ) {
        if(is_string($strings)) {
            $strings = json_decode($strings, true, 512, JSON_THROW_ON_ERROR);
        }
        $this->setAll($strings);
    }

    public function toArray(): array
    {
        return $this->strings;
    }

    public function setAll(array $strings): static
    {
        $this->strings = [];
        foreach ($strings as $string) {
            $this->add($string);
        }
        return $this;
    }

    public function add(string $string): static
    {
        $this->strings[] = $string;
        $this->cleanup();
        return $this;
    }

    public function cleanup(
        bool $removeDuplicates = false
    ): static
    {
        $this->strings = array_map('trim', $this->strings);
        $this->strings = array_filter($this->strings, fn($string) => !empty(strip_tags($string)));
        if($removeDuplicates) {
            $this->strings = array_unique($this->strings);
        }
        // $this->strings = array_values($this->strings);
        return $this;
    }

    public function __toString(): string
    {
        return implode(',', $this->strings);
    }

    public function toString(string $separator = ','): string
    {
        return implode($separator, $this->strings);
    }


    public function jsonSerialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function count(): int
    {
        return count($this->strings);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->strings[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->strings[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Value must be a string');
        }
        switch (true) {
            case $offset === null:
                // $this->add($value);
                $this->strings[] = $value;
                break;
            default:
                $this->strings[$offset] = $value;
                break;
        }
        $this->cleanup();
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->strings[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayCollection($this->strings);
    }


}