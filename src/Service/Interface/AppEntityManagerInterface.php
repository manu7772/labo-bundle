<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Component\Opresult;
use Aequation\LaboBundle\Service\Interface\ServiceInterface;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\OwnerInterface;
use Aequation\LaboBundle\Repository\Interface\CommonReposInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;

interface AppEntityManagerInterface extends ServiceInterface
{

    public function getAppService(): AppServiceInterface;
    public function getNew(string $classname = null, callable $postCreate = null, string $uname = null): AppEntityInterface|false;
    public function getModel(string $classname = null, callable $postCreate = null, string|array|null $event = null): AppEntityInterface|false;
    public function initEntity(AppEntityInterface $entity, ?callable $postCreate = null, string|array|null $event = null): AppEntityInterface;
    public function setManagerToEntity(AppEntityInterface $entity, string|array|null $event = null): AppEntityInterface;
    // public function getClone(AppEntityInterface $entity): AppEntityInterface;
    public function save(AppEntityInterface $entity, bool|Opresult $opresultException = true): bool;
    public function delete(AppEntityInterface $entity, bool|Opresult $opresultException = true): bool;
    // Environment
    public function isDev(): bool;
    public function isProd(): bool;
    public function isTest(): bool;
    public function getEnvironment(): string;
    // User
    public function getUser(): ?LaboUserInterface;
    public function getMainSAdmin(): ?LaboUserInterface;
    public function getMainAdmin(): ?LaboUserInterface;
    public function isGranted(mixed $attributes, mixed $subject = null): bool;
    public function isUserGranted(LaboUserInterface $user, $attributes, $object = null, string $firewallName = 'none'): bool;
    // EM & REPO
    public function getEntityManager(): EntityManagerInterface;
    public function getUnitOfWork(): UnitOfWork;
    public function getRepository(string $classname = null): CommonReposInterface;

    // public function getEntityNamespaces(): array;
    public static function isAppEntity(string|object $classname): bool;
    public function getEntityNames(bool $asShortnames = false, bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function getEntityShortname(string $classname): string;
    public function getClassnameByShortname(string $shortname): string|false;
    public function entityExists(string|object $classname, bool $allnamespaces = false, bool $onlyInstantiables = false): bool;
    public function getEntityNamesChoices(bool $asHtml = false, bool $icon = true, bool $allnamespaces = false, bool $onlyInstantiables = false): array;
    public function getEntityClassesOfInterface(string|array $interfaces, bool $allnamespaces = false): array;
    public static function getEntityNameAsHtml(string|AppEntityInterface $classOrEntity, bool $icon = true, bool $classname = true): string;
    public function flush(): void;

    public function findEntityByUniqueValue(string $value): ?AppEntityInterface;
    public function findEntityByEuid(string $euid): ?AppEntityInterface;

    public function getScheduledForInsert(string|array|callable $filter = null): array;
    public function getScheduledForUpdate(string|array|callable $filter = null): array;
    public function getScheduledForDelete(string|array|callable $filter = null): array;
    public function isManaged(AppEntityInterface $entity): bool;

    public static function getEntityServiceID(string|AppEntityInterface $objectOrClass): ?string;
    public function getEntityService(string|AppEntityInterface $objectOrClass): ?AppEntityManagerInterface;
    public function defineEntityOwner(OwnerInterface $entity, bool $replace = false): static;
    public function getClassMetadata(string|AppEntityInterface $objectOrClass = null): ?ClassMetadata;
    public function getEntityMetadataReport(string $classname = null): ClassmetadataReport;
    // Events
    public function dispatchEvent(AppEntityInterface $entity, string|array $typeEvent, array $data = []): static;

    public static function getUniqueFields(string $classname, bool|null $flatlisted = false): array;

}