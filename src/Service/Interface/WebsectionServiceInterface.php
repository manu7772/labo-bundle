<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Model\Interface\WebsectionInterface;
use Symfony\Component\Finder\SplFileInfo;

interface WebsectionServiceInterface extends ItemServiceInterface
{

    public function getPreferedWebsections(): array;
    public function getWebsectionsCount(bool $onlyActives = false, array $criteria = []): int;
    public function findWebsection(int|string|null $websection): ?WebsectionInterface;
    public static function stripTwigfile(string|SplFileInfo $twigfile, bool $removeExtension = false): string;
    public function listWebsectionModels(bool $asChoiceList = false, array|string|null $filter_types = null, string|array|null $paths = null): array;
    public function getSectiontypeOfFile(string $filename): ?string;
    public function getSectiontypes(array|string|null $filter_types = null): array;
    public function getWebsectionModels(array|string|null $filter_types = null): array;
    public function getDefaultWebsectionModel(array|string|null $filter_types = null, bool $findAnyway = true): ?string;
    public function setDefaultWebsectionValues(WebsectionInterface $entity, mixed $data = [], $event = null): void;

}