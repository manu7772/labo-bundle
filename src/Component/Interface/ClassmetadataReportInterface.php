<?php
namespace Aequation\LaboBundle\Component\Interface;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;

use Serializable;
use Twig\Markup;

interface ClassmetadataReportInterface extends Serializable
{

    public const REGEX_ORM_MAPPING = '/^Doctrine\\\\ORM\\\\Mapping/';

    public function serialize(): ?string;
    public function __serialize(): array;
    public function unserialize(string $data);
    public function __unserialize(array $data): void;
    public function toArray(): array;
    public function isValid(): bool;
    public function setEntity(AppEntityInterface $entity): static;
    public function getEntity(): ?AppEntityInterface;
    public function getModel(): ?AppEntityInterface;
    public function setClassname(string $classname): bool;
    public function getData(): array|false;
    public function __isset($name);
    public function __get($name);
    public function __call($name, $arguments);
    public function getMananger(): ?AppEntityManagerInterface;
    public function getManagerID(): ?string;
    public function getShortname(): string;
    public function getShortname_lower(): string;
    public function getInterfaces(): array;
    public function getConstants(): array;
    public function getTraits(): array;
    public function getAllTraits(bool $flatten = false): array;
    public function getBreadcrumbName(string $link = ' \\ ', bool $asHtml = true): Markup;
    public function getPhpChilds(bool $onlyInstantiables = false): array;
    public function getParentReport(): ?ClassmetadataReport;
    public function getChildrenReports(): iterable;
    public function getParentClasses(bool $reverse = false): array;
    public function getPhpParents(bool $reverse = false): array;
    public function getPhpRootParent(): ?string;
    public function isDoctrineRoot(): bool;
    public function isPhpRoot(): bool;
    public function isAppEntity(): bool;
    public function isInstantiable(): bool;
    public function getUniqueFields(bool $flatlisted = false): array;
    public function arraySortValue(ClassmetadataReport $otherReport): int;
    public static function sortReports(array &$reports): void;
    public function computeReport(): static;
    public function getClassAttributes(string $attributeClass, bool $searchParents = true): array;
    public function getPropertyAttributes(string $attributeClass, bool $searchParents = true): array;
    public function getMethodAttributes(string $attributeClass, bool $searchParents = true): array;
    public function getConstantAttributes(string $attributeClass, bool $searchParents = true): array;
    public function hasErrors(): bool;
    public function getErrors(): array;

}