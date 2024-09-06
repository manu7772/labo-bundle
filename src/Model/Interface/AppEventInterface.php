<?php
namespace Aequation\LaboBundle\Model\Interface;

use ReflectionMethod;

interface AppEventInterface
{

    public function setMethod(ReflectionMethod|string $method): static;
    public function hasGroup(string $group): bool;
    public function isApplicable(AppEntityInterface $entity, string $group): bool;
    public static function getNewEvents(): array;
    public static function getPersistedEvents(): array;
    public static function getEvents(): array;
    public static function hasEvent(string $event): bool;
    public function __serialize(): array;
    public function __unserialize(array $data): void;

}