<?php
namespace Aequation\LaboBundle\Model\Interface;


interface OwnerInterface
{
    public function getOwner(): ?LaboUserInterface;
    public function setOwner(?LaboUserInterface $owner): static;
}