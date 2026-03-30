<?php
namespace Aequation\LaboBundle\Component\Interface;

// Symfony
use Symfony\Component\HttpFoundation\File\File;
// PHP
use JsonSerializable;

interface ImageOperationsInterface extends JsonSerializable
{
    public function __construct(array $operations = []);
    // static operations
    public static function getAllOperations(): array;
    public static function getAllOperationNames(): array;
    // instance operations
    public function jsonSerialize(): mixed;
    public function setImage(string|File $image): static;
    public function getImage(): ?File;
    public function hasOperation(string $name): bool;
    public function getOperations(): array;
    public function setOperations(array $operations): static;
    public function apply(null|string|array $names = null): bool;
}