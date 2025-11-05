<?php
namespace Aequation\LaboBundle\Model\Interface;

use Symfony\Component\HttpFoundation\File\File;

interface ImageInterface extends ItemInterface
{
    public function setFile(File $file): static;
    public function getFile(): File|null;
    public function updateName(): static;
    public function getFilename(): ?string;
    public function setFilename(?string $filename): static;
    public function getSize(): ?int;
    public function setSize(?int $size): static;
    // public function removeLinkedto(): bool;
    // public function getLinkedto(): ?ImageOwnerInterface;
    public function getDimensions(bool $asArray = false): null|string|array;
    public function setDimensions(mixed $dimensions): static;
    public function setDeleteImage(bool $deleteImage): static;
    public function isDeleteImage(): bool;
    public function getImagefilter(): ?string;
    public function setImagefilter(?string $imagefilter): static;
    public function getLiipDefaultFilter(): string;
    public function setLiipDefaultFilter(string $liipDefaultFilter): static;
    public function getMime(): ?string;
    public function setMime(?string $mime): static;
}