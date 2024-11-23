<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsAlias(AppRoleHierarchyInterface::class, public: true)]
class AppRoleHierarchy extends RoleHierarchy implements AppRoleHierarchyInterface
{

    /**
     * @param array<string, list<string>> $hierarchy
     */
    public function __construct(
        #[Autowire(param: 'security.role_hierarchy.roles')]
        array $hierarchy
    )
    {
        parent::__construct($hierarchy);
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Get name of service
     * 
     * @return string
     */
    public function getName(): string
    {
        return static::class;
    }

    /**
     * Get roles map
     * 
     * @return array
     */
    public function getRolesMap(): array
    {
        return $this->map;
    }

    /**
     * Get flat map of roles
     * 
     * @return array
     */
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

    /**
     * Sort roles by hierarchy - From lower to higher
     * 
     * @param array &$roles
     * @param bool $filter_main_roles = false
     * @return void
     */
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
     * 
     * @return array
     */
    public function getMainRoles(): array
    {
        $roles = array_keys($this->map);
        // Add ROLE_USER as first role
        array_unshift($roles, LaboUserInterface::ROLE_USER);
        $this->sortRoles($roles, false);
        return $roles;
    }

    /**
     * Returns only main roles in $roles : keys of map
     * (removes some secondary roles like ROLE_ALLOWED_TO_SWITCH, etc.)
     * 
     * @param array $roles
     * @return array
     */
    public function getReachableRoleNames(array $roles): array
    {
        $roles = parent::getReachableRoleNames($roles);
        $this->sortRoles($roles, false);
        return $roles;
    }

    /**
     * Get higher role of $roles in hierarchy
     * 
     * @param array $roles
     * @return string|false
     */
    public function getHigherRole(array $roles): string|false
    {
        $this->sortRoles($roles, true);
        return end($roles);
    }

    /**
     * Get lower role of $roles in hierarchy
     * 
     * @param array $roles
     * @return string|false
     */
    public function getLowerRole(array $roles): string|false
    {
        $this->sortRoles($roles, true);
        return reset($roles);
    }

    /**
     * Get inferior roles of $max role given
     *
     * @param string $max
     * @return array
     */
    public function getInferiorRoles(string $max): array
    {
        return array_filter($this->getReachableRoleNames($this->getMainRoles()), fn ($role) => $role !== $max );
    }

    /**
     * Get only main roles in $roles : keys of map
     * (removes some secondary roles like ROLE_ALLOWED_TO_SWITCH, etc.)
     * 
     * @param [string] $roles
     * @return array
     */
    public function filterMainRoles(array $roles): array
    {
        return $this->getReachableRoleNames($roles);
        // $mainroles = $this->getMainRoles();
        // return array_filter($roles, function($role) use ($mainroles):bool { return in_array($role, $mainroles); });
    }

    /**
     * Get roles for form choices by User
     * 
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