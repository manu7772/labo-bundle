<?php
namespace Aequation\LaboBundle\Service\Interface;

use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Aequation\LaboBundle\Model\Interface\PdfizableInterface;

interface PdfServiceInterface extends ItemServiceInterface
{

    public function outputHtml(string $htmlContent, string $paper = 'A4', string $orientation = 'portrait', array $options = []): string;
    public function getBrowserPath(PdfInterface $pdf): string;
    public function outputDoc(PdfizableInterface $pdf): string;

}