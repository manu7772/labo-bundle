<?php
namespace Aequation\LaboBundle\Component;

// use Symfony\Component\Finder\SplFileInfo as SymfonySplFileInfo;

use Symfony\Component\Finder\Finder;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Iterator;
use IteratorIterator;
use SplFileInfo;

class FinderLabo extends Finder
{

    public function __construct(
        protected SplFileLabo|string $file,
    )
    {
        if(is_string($this->file)) $this->file = new SplFileLabo($this->file);
        if(!$this->file->isDir()) {
            throw new Exception(vsprintf('Error %s line %d: %s is not a directory!', [__METHOD__, __LINE__, $this->file->getPathname()]));
        }
        parent::__construct();
        $this->ignoreUnreadableDirs();
        $this->depth(0);
        $this->in($this->file->getPathname());
    }

    public function toArray(): array
    {
        return iterator_to_array($this, true);
    }

    public function getChildren(
        string|int|array $depth = 0,
    ): Iterator
    {
        $array = new ArrayCollection();
        if($this->file->isDir()) {
            $this->depth($depth);
            foreach (parent::getIterator() as $filepath => $splfileinfo) {
                $array->set($filepath, new SplFileLabo($splfileinfo));
            }
        }
        return new IteratorIterator($array);
    }

    public function getChildrenArray(
        string|int|array $depth = 0,
    ): array
    {
        return iterator_to_array($this->getChildren($depth), true);
    }


}