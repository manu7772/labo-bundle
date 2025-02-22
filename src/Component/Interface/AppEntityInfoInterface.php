<?php
namespace Aequation\LaboBundle\Component\Interface;

use Aequation\LaboBundle\Repository\Interface\CommonReposInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Serializable;

interface AppEntityInfoInterface extends Serializable
{

    public function isValid(): bool;
    public function getManager(): AppEntityManagerInterface;
    public function getRepository(): CommonReposInterface;

    public function isPersisted(): bool;
    public function isNew(): bool;

    public function serialize(): ?string;
    public function unserialize(string $data): void;

    // internal values
    public function setRelationOrderLoaded(bool $loaded): void;
    public function isRelationOrderLoaded(bool $default = false): bool;

}