<?php
namespace Aequation\LaboBundle\Model\Final;

use Aequation\LaboBundle\Model\Interface\MenuInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Interface\PreferedInterface;
use Aequation\LaboBundle\Model\Interface\ScreenableInterface;

interface FinalMenuInterface extends MenuInterface, PreferedInterface, SlugInterface, ScreenableInterface
{
    
}