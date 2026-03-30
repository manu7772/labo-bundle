<?php
namespace Aequation\LaboBundle\Model\Interface;

use Aequation\LaboBundle\Component\Interface\ImageOperationsInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

interface ImageInterface extends ItemInterface
{
    public function getDefaultLiipFilter(): string;
    public static function getDefaultLiipFilterChoiceArea(): array;
    public function getThumbnailLiipFilter(): string;
    public static function getAvailableLiipFilters(): array|true;
    public function setFile(File $file): static;
    public function getFile(): File|null;
    public function getFilepathname(
        $filter = null,
        array $runtimeConfig = [],
        $resolver = null,
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): ?string;
    public function getLiipDefaultFilter(): string;
    public function setLiipDefaultFilter(string $liipDefaultFilter): static;
    public function updateName(): static;
    public function getFilename(): ?string;
    public function setFilename(?string $filename): static;
    public function getSize(): ?int;
    public function setSize(?int $size): static;
    public function getMime(): ?string;
    public function setMime(?string $mime): static;
    public function getOriginalname(): ?string;
    public function setOriginalname(?string $originalname): static;
    public function getDimensions(bool $asArray = false): null|string|array;
    public function setDimensions(mixed $dimensions): static;
    public function getImagefilter(): ?string;
    public function getImagefilterName(): ?string;
    public function setImagefilter(?string $imagefilter): static;
    public function getDescription(): ?string;
    public function setDescription(?string $description): static;
    public function setDeleteImage(bool $deleteImage): static;
    public function isDeleteImage(): bool;
    public function getImageOperations(): ImageOperationsInterface;
    public function setImageOperations(ImageOperationsInterface|array $imageOperations): static;
}