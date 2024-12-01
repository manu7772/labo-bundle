<?php
namespace Aequation\LaboBundle\Model\Interface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


interface PdfizableInterface
{
    public function isPdfExportable(): bool;
    public function getFilename(): ?string;
    public function getMime(): ?string;
    public function getPaper(): ?string;
    public function getContent(): ?string;
    public function getOrientation(): ?string;
    public function getSourcetype(): int;
    public function getPdfUrlAccess(?int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL, string $action = 'inline',): ?string;

}