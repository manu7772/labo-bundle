<?php
namespace Aequation\LaboBundle\Service\Interface;

use Symfony\Component\Finder\SplFileInfo;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Model\Interface\ScreenableInterface;

interface LaboWebpageServiceInterface extends EcollectionServiceInterface
{

    public const FILES_FOLDER = 'webpage/';
    public const TWIGFILE_MATCH = '/(\\.html?)\\.twig$/i';

    public function getPreferedWebpage(): ?FinalWebpageInterface;
    public function getWebpagesCount(bool $onlyActives = false, array $criteria = []): int;
    public function findWebpage(int|string|null $webpage): ?FinalWebpageInterface;
    public static function stripTwigfile(string|SplFileInfo $twigfile, bool $removeExtension = false): string;
    public function getWebpageModels(): array;
    public function getWebpageChoices(?ScreenableInterface $screenable = null): array;

}