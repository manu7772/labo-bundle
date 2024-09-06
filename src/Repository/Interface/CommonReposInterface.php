<?php
namespace Aequation\LaboBundle\Repository\Interface;

use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\HasOrderedInterface;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\ORM\QueryBuilder;

interface CommonReposInterface extends ServiceEntityRepositoryInterface
{

    public function getChoicesForType(string $field, string $context = 'form_choice', ?array $search = []): array;
    public function tryFindExistingEntity(string|array $dataOrUname, ?array $uniqueFields = null): ?AppEntityInterface;
    public function findEntityByEuidOrUname(string $uname): ?AppEntityInterface;
    public function findAllSlugs(?int $exclude_id = null): array;
    public function hasField(string $name): bool;
    public function hasRelation(string $name): bool;

    // Collections utilities
    public function getCollectionChoices(string|HasOrderedInterface $classOrEntity, string $property): array;
    public static function getQB_orderedChoicesList(QueryBuilder $qb, string|HasOrderedInterface $classOrEntity, string $property): QueryBuilder;
    public static function checkFromAndSearchClasses(QueryBuilder $qb, array|string $classes, bool $throwsException = false): bool;

    // QueryBuilders
    public function getQB_findBy(?array $search, string $context = 'auto'): QueryBuilder;

}