<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Model\Interface\LaboCategoryInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;

interface LaboUserServiceInterface extends AppEntityManagerInterface
{

    public function addMeToSuperAdmin(): ?LaboUserInterface;
    public function getMainSAdmin(): ?LaboUserInterface;
    public function getMainAdmin(): ?LaboUserInterface;
    public function userExists(string|int $value, bool $contextFilter = false): bool;
    public function checkUserExceptionAgainstStatus(LaboUserInterface $user): void;
    public function getUser(): ?LaboUserInterface;
    public function FindOrCreateUserByEmail(?string $email): LaboUserInterface|false;
    public function findUser(string|int $emailOrId, bool $excludeDisabled = false): LaboUserInterface|null;
    public function findUsersByCategories(string|LaboCategoryInterface|iterable $categorys, bool $onlyActive = true): array;
    public function isLoggable(array|LaboUserInterface $user): bool;

}