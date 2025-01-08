<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Entity\Item;
use ReflectionProperty;

interface HasOrderedInterface extends AppEntityInterface
{

    public const ITEMS_ACCEPT = [
        'items' => [Item::class],
    ];

    public function updateRelationOrder(): bool;
    public function loadedRelationOrder(): static;
    public function getRelationOrderDetails(): string;
    public function getRelationOrderNames(string|ReflectionProperty|null $property = null): array;
    public function getRelationOrder(bool $asJson = false): array|string|false;
    public function getPropRelationOrder(string|ReflectionProperty $property): array;
    public function changePosition(Item $item, string $position): bool;
}