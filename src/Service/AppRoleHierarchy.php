<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsAlias(AppRoleHierarchyInterface::class)]
class AppRoleHierarchy extends RoleHierarchy implements AppRoleHierarchyInterface
{

    /**
     * @param array<string, list<string>> $hierarchy
     */
    public function __construct(
        #[Autowire(param: 'security.role_hierarchy.roles')] array $hierarchy
    )
    {
        parent::__construct($hierarchy);
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return static::class;
    }

    public function getRolesMap(): array
    {
        return $this->map;
    }

    public function getRolesFlatMap(): array
    {
        $flat = [];
        foreach ($this->map as $role => $grantedRoles) {
            foreach ($grantedRoles as $grantedRole) {
                if(!in_array($grantedRole, $flat)) $flat[] = $grantedRole;
            }
            if(!in_array($role, $flat)) $flat[] = $role;
        }
        return $flat;
    }

    public function sortRoles(
        array &$roles,
        bool $filter_main_roles = false
    ): void
    {
        if($filter_main_roles) {
            $roles = $this->filterMainRoles($roles);
        }
        $sorteds = [];
        foreach ($this->getRolesFlatMap() as $role) {
            if(in_array($role, $roles)) {
                $sorteds[] = $role;
            }
        }
        $roles = $sorteds;
    }

    /**
     * Get only main roles : keys of map
     * (removes some secondary roles like ROLE_ALLOWED_TO_SWITCH, etc.)
     * @return array
     */
    public function getMainRoles(): array
    {
        $roles = array_keys($this->map);
        array_unshift($roles, LaboUserInterface::ROLE_USER);
        return $roles;
    }

    public function getReachableRoleNames(array $roles): array
    {
        $roles = parent::getReachableRoleNames($roles);
        $this->sortRoles($roles, false);
        return $roles;
    }

    public function getHigherRole(array $roles): string|false
    {
        $this->sortRoles($roles, true);
        return end($roles);
    }

    public function getLowerRole(array $roles): string|false
    {
        $this->sortRoles($roles, true);
        return reset($roles);
    }

    public function getInferiorRoles(string $max): array
    {
        return array_filter($this->getReachableRoleNames($this->getMainRoles()), function ($role) use ($max):bool { return $role !== $max; });
    }

    /**
     * Filter only main roles : keys of map
     * (removes some secondary roles like ROLE_ALLOWED_TO_SWITCH, etc.)
     * @param [string] $roles
     * @return array
     */
    public function filterMainRoles(array $roles): array
    {
        $mainroles = $this->getMainRoles();
        return array_filter($roles, function($role) use ($mainroles):bool { return in_array($role, $mainroles); });
    }

    /**
     * Get roles for form choices by User
     * @param UserInterface $user
     * @return array
     */
    public function getRolesChoices(UserInterface $user): array
    {
        $choices = [];
        if($user instanceof LaboUserInterface) {
            $max = $user->getHigherRole();
        } else {
            $roles = $user->getRoles();
            $this->sortRoles($roles, true);
            $max = end($roles);
        }
        foreach ($this->getMainRoles() as $role) {
            $choices[] = $role;
            if($role === $max) break;
        }
        return array_combine($choices, $choices);
    }

}