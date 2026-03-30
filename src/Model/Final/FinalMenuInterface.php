<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\HasCurrentItemInterface;
use Aequation\LaboBundle\Model\Interface\MenuInterface;
use Aequation\LaboBundle\Model\Interface\PreferedInterface;
use Aequation\LaboBundle\Model\Interface\ScreenableInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;

interface FinalMenuInterface extends MenuInterface, HasCurrentItemInterface, PreferedInterface, SlugInterface, ScreenableInterface
{
    
}