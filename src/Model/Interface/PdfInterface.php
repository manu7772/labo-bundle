<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Entity\Item;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface PdfInterface extends ItemInterface, SlugInterface, PdfizableInterface
{

    // SourceType
    public function getSourcetype(): int;
    public function getSourcetypeName(): string;
    public function setSourcetype(int|string $sourcetype): static;
    public static function getSourcetypeChoices(): array;
    // Paper
    public function getPaper(): ?string;
    public function setPaper(?string $paper): static;
    public static function getPaperChoices(): array;
    // Orientation
    public function getOrientation(): ?string;
    public function setOrientation(?string $orientation): static;
    public static function getOrientationChoices(): array;
    // File
    public function getFile(): File|null;
    public function getFilepathname($filter = null, array $runtimeConfig = [], $resolver = null, $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): ?string;
    public function updateName(): static;
    public function getFilename(bool|DateTimeInterface $versioned = false): ?string;
    public function setFilename(?string $filename): static;
    public function getSize(): ?int;
    public function setSize(?int $size): static;
    public function getMime(): ?string;
    public function setMime(?string $mime): static;
    public function getOriginalname(): ?string;
    public function setOriginalname(?string $originalname): static;
    public function getDescription(): ?string;
    public function setDescription(?string $description): static;
    public function getContent(): ?string;
    public function setContent(?string $content): static;

    public function getPdfowner(): ?Item;
    public function setPdfowner(?Item $pdfowner): static;

}