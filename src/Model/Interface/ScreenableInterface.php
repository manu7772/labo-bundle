<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;


interface ScreenableInterface
{

    public function getWebpage(): ?FinalWebpageInterface;
    public function setWebpage(?FinalWebpageInterface $webpage): static;

}