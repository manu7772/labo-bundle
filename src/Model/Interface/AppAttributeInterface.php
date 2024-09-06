<?php
namespace Aequation\LaboBundle\Model\Interface;

interface AppAttributeInterface
{

    public function getClassObject(): ?object;
    public function setClass(object $class): static;

}