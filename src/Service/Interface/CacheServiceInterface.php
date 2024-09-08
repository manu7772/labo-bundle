<?php
namespace Aequation\LaboBundle\Service\Interface;

use SplFileInfo;

interface CacheServiceInterface extends ServiceInterface
{

    // Dev shortcuts
    public function isDevShortcut(string $key): bool;
    public function setDevShortcut(string $key, bool $enabled): static;
    public function setDevShortcutAll(bool $onoff): static;
    public function toggleDevShortcut(string $key): static;

    public function get(string $key, callable $callback, string $commentaire = null, float $beta = null, array $metadata = null): mixed;
    public function delete(string $key): bool;
    public function deleteAll(): bool;
    public function getKeys($withCommentaires = true): array;
    public function hasKey(string $key): bool;
    public function cacheClear(string $method = 'exec'): static;
    public function getCacheDir(): ?SplFileInfo;
    public function getCacheDirs(int $depth = 0): array;
    // PhpData
    // public function getPhpData(string $name = null, mixed $default = null): mixed;
    // public function setPhpData(string $name, mixed $data): static;
    // public function updatePhpData(): static;
    // public function getPhpDataPath(): string|false;

}