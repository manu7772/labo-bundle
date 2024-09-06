<?php
namespace Aequation\LaboBundle\Model\Interface;

interface UnameInterface extends AppEntityInterface
{

    public function attributeEntity(UnamedInterface $entity, string $uname = null): static;
    public function getUname(): ?string;
    // public function setUname(string $uname): static;
    public function getEuidofentity(): ?string;
    // public function setEuidofentity(string $euidofentity): static;

}

