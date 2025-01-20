<?php
namespace Aequation\LaboBundle\Repository\Interface;

use Doctrine\ORM\QueryBuilder;

interface LaboUserRepositoryInterface extends CommonReposInterface
{
    public function userExists(string|int $value, bool $contextFilter = false): bool;
    public function findUserSimpleData(string|int $value, bool $contextFilter = false): array;
    public function findAllUsers(null|string|array $roles = null): array;
    public function qb_findAllUsers(null|string|array $roles = null, ?QueryBuilder $query = null): QueryBuilder;
    public function qb_filterByEntreprises(int|array $entreprise_ids, ?QueryBuilder $query = null): QueryBuilder;
    public function qb_filterByCategories(int|array $category_ids, ?QueryBuilder $query = null): QueryBuilder;
}