<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Model\Interface\WebpageInterface;
use Symfony\Component\Finder\SplFileInfo;

interface WebpageServiceInterface extends EcollectionServiceInterface
{

    public const FILES_FOLDER = 'webpage/';
    public const TWIGFILE_MATCH = '/(\\.html?)\\.twig$/i';

    public function getPreferedWebpage(): ?WebpageInterface;
    public function getWebpagesCount(bool $onlyActives = false, array $criteria = []): int;
    public function findWebpage(int|string|null $webpage): ?WebpageInterface;
    public static function stripTwigfile(string|SplFileInfo $twigfile, bool $removeExtension = false): string;
    public function getWebpageModels(): array;

}