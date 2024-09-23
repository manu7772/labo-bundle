<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface LaboUserInterface extends UserInterface, PasswordAuthenticatedUserInterface, AppEntityInterface, EnabledInterface, CreatedInterface
{

    public const ROLE_USER = "ROLE_USER";

    public function setRoleHierarchy(AppRoleHierarchyInterface $roleHierarchy): void;
    public function getHigherRole(): string;
    public function getRolesChoices(UserInterface $user = null): array;
    public function isVerified(): ?bool;
    public function isDisabled(): bool;
    public function isExpired(): bool;
    public function canLogin(): bool;
    public function setPassword(string $password): static;
    public function updateLastLogin(): static;
    public function setEmail(string $email): static;
    public function getEmail(): ?string;
    public function isDarkmode(): ?bool;
    public function setDarkmode(bool $darkmode): static;
    public function getPlainPassword(): ?string;
    public function setIsVerified(bool $isVerified): static;
    public function getFirstname(): ?string;
    public function getLastname(): ?string;
    public function hasRole(string $role): bool;
    public function addRole(string $role): static;

}

