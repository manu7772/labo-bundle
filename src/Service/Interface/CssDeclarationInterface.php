<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Component\CssManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Process\Process;

interface CssDeclarationInterface
{

    public function setFilepath(string $filepath, bool $create = true): static;
    public function setFilename(string $filename): static;
    public function getClasses(): array;
    public function getSortedAllFinalClasses(bool $refresh = false): array;
    public function addClasses(string|array $classes): int; // nombre de classes ajoutées
    public function removeClasses(string|array $classes): int; // nombre de classes retirées
    public function isRemovable(string $class): bool;
    public function getComputedClasses(): array;
    public function readClassesList(bool $refresh = false): array;
    public function refreshClasses(): bool;
    public function saveClasses(): bool;
    public function resetAll(): bool;
    public function registerHtmlContent(string $action, array $entities, bool $update = false): bool;
    public function getCssForm(?CssManager $cssManager = null): FormInterface;
    // Tailwind
    public function buildTailwindCss(bool $watch, bool $poll, bool $minify, ?callable $callback = null): Process;

}