<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

interface AppRoleHierarchyInterface extends RoleHierarchyInterface
{

    public function getRolesMap(): array;
    public function getRolesFlatMap(): array;
    public function getMainRoles(): array;
    public function filterMainRoles($roles): array;
    public function getRolesChoices(LaboUserInterface $user): array;

}