<?php
namespace Aequation\LaboBundle\Model\Interface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use DateTimeInterface;


interface PdfizableInterface
{
    public function isPdfExportable(): bool;
    public function getFilename(bool|DateTimeInterface $versioned = false): ?string;
    public function getMime(): ?string;
    public function getPaper(): ?string;
    public function getContent(): ?string;
    public function getOrientation(): ?string;
    public function getSourcetype(): int;
    public function getPdfUrlAccess(?int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL, string $action = 'inline',): ?string;

}