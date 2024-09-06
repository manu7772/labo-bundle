<?php
namespace Aequation\LaboBundle\Component;

use Symfony\Component\Finder\Finder;
use Doctrine\Common\Collections\Collection;

use SplFileInfo;
use Countable;
use Iterator;
use IteratorAggregate;
use IteratorIterator;

class SplFileLabo extends SplFileInfo implements IteratorAggregate, Countable
{

    public readonly ?SplFileLabo $rootparent;
    public readonly ?FinderLabo $finder;

    public function __construct(
        public readonly string $filename,
        public readonly ?SplFileLabo $parent = null
    )
    {
        parent::__construct(filename: $this->filename);
        $this->finder = $this->isDir()
            ? new FinderLabo($this)
            : null;
        $this->rootparent = $this->parent instanceof SplFileLabo
            ? $this->parent->rootparent ?? $this->parent
            : null;
    }

    public function getChildren(): Iterator
    {
        return $this->finder
            ? $this->finder->getChildren()
            : new IteratorIterator(new Collection());
    }

    public function getIterator(): Iterator
    {
        return $this->getChildren();
    }

    public function count(): int
    {
        return $this->finder
            ? $this->finder->count()
            : 0;
    }

    public function __isset($name)
    {
        return $this->finder && property_exists($this->finder, $name);
    }

    public function __get($name)
    {
        return $this->finder->$name;
    }

    public function __call($name, $arguments)
    {
        return $this->finder->$name(...$arguments);
    }

}