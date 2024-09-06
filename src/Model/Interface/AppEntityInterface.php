<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Component\Interface\AppEntityInfoInterface;
use Serializable;
use Stringable;

interface AppEntityInterface extends Stringable, Serializable
{
    // Interface of all entities
    public function getId(): ?int;
    public function getEuid(): ?string;
    public function getUnameThenEuid(): string;
    public function defineUname(string $uname): static;
    public function __toString(): string;
    public function __construct_entity(): void;
    // Clone
    public function _isClone(): bool;
    public function _setClone(bool $_isClone): static;
    public function _removeIsClone(): static;
    public function __clone_entity(): void;
    // Classname
    public function getClassname(): string;
    // Shortname
    public function getShortname(bool $lowercase = false): string;
    public static function _shortname(string $type, string $prefix = null, string $suffix = null): string;
    public function getShortnameFormated(
        string $type = 'camel',
    ): string;
    public function getShortnameDecorated(
        string $type = 'camel',
        string $prefix = null,
        string $suffix = null,
    ): string;
    // Icon
    public static function getIcon(bool|string $asClass = false, array|string $addClasses = []): string;
    // Special AppManaged
    public function __setAppManaged(AppEntityInfoInterface $appManaged): void;
    public function __isAppManaged(): bool;
    // Serialization
    public function serialize(): ?string;
    public function unserialize(string $data): void;

}