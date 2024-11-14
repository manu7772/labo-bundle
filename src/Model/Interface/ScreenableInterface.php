<?php
namespace Aequation\LaboBundle\Model\Interface;


interface ScreenableInterface
{

    public function getWebpage(): WebpageInterface;
    public function setWebpage(?WebpageInterface $webpage): static;

}