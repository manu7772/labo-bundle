<?php
namespace Aequation\LaboBundle\Component\Interface;

// Symfony
use Twig\Markup;
// PHP
use Stringable;

interface TextContentsInterface extends WireEmbeddedInterface
{
    public function __construct(...$texts);
    public function getElements(): array;
    public function getRaw(): static;
    public function getRaws(): Markup;
    public function setTexts(array $texts): void;
    public function addText(?Stringable $text): static;
    public function getText(int $index): ?string;
}