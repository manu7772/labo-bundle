<?php
namespace Aequation\LaboBundle\Service\Interface;

use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface AppRoleHierarchyInterface extends RoleHierarchyInterface
{

    public function getRolesMap(): array;
    public function getRolesFlatMap(): array;
    public function sortRoles(array &$roles, bool $filter_main_roles = false): void;
    public function getMainRoles(): array;
    public function getHigherRole(array $roles): string|false;
    public function getLowerRole(array $roles): string|false;
    public function getInferiorRoles(string $max): array;
    public function filterMainRoles(array $roles): array;
    public function getRolesChoices(UserInterface $user): array;

}