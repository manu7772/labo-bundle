<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

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

    /**
     * Filter only main roles : keys of map
     * (removes some secondary roles like ROLE_ALLOWED_TO_SWITCH, etc.)
     * @param [string] $roles
     * @return array
     */
    public function filterMainRoles($roles): array
    {
        $mainroles = $this->getMainRoles();
        return array_filter($roles, function($role) use ($mainroles):bool { return in_array($role, $mainroles); });
    }

    /**
     * Get roles for form choices by User
     * @param LaboUserInterface $user
     * @return array
     */
    public function getRolesChoices(LaboUserInterface $user): array
    {
        $choices = [];
        $max = $user->getHigherRole();
        foreach ($this->getMainRoles() as $role) {
            $choices[] = $role;
            if($role === $max) break;
        }
        return array_combine($choices, $choices);
    }

}