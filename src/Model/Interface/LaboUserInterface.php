<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Model\Final\FinalCategoryInterface;
use Aequation\LaboBundle\Model\Final\FinalEmailinkInterface;
use Aequation\LaboBundle\Model\Final\FinalPhonelinkInterface;
use Aequation\LaboBundle\Model\Final\FinalAddresslinkInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
// Symfony
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
// PHP
use DateTimeImmutable;

interface LaboUserInterface extends UserInterface, PasswordAuthenticatedUserInterface, AppEntityInterface, EnabledInterface, CreatedInterface
{

    public const ROLE_USER = "ROLE_USER";

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
    public function getLastLogin(): ?DateTimeImmutable;
    public function getExpiresAt(): ?DateTimeImmutable;
    public function getCategorys(): Collection;
    public function addCategory(FinalCategoryInterface $category): static;
    public function removeCategory(FinalCategoryInterface $category): static;
    public function removeCategorys(): static;
    
    /** ROLES */
    public function getReachableRoles(): array;
    public function sortRoles(): void;
    public function setRoleHierarchy(AppRoleHierarchyInterface $roleHierarchy): void;
    public function getRolesChoices(UserInterface $user = null): array;
    public function hasRole(string $role): bool;
    public function addRole(string $role): static;
    public function removeRole(string $role): static;
    public function setRoles(array $roles): static;
    public function getRoles(): array;
    public function getInferiorRoles(): array;
    public function getLowerRole(): string;
    public function getHigherRole(): string;

    /** COORDINATES */
    public function getAddresses(): Collection;
    public function addAddress(FinalAddresslinkInterface $address): static;
    public function removeAddress(FinalAddresslinkInterface $address): static;
    public function getEmails(): Collection;
    public function addEmail(FinalEmailinkInterface $email): static;
    public function removeEmail(FinalEmailinkInterface $email): static;
    public function getPhones(): Collection;
    public function addPhone(FinalPhonelinkInterface $phone): static;
    public function removePhone(FinalPhonelinkInterface $phone): static;

}

