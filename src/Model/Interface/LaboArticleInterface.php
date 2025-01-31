<?php
namespace Aequation\LaboBundle\Model\Interface;

use DateTimeInterface;

interface LaboArticleInterface extends SlugInterface
{
    public function getTitle(): ?string;
    public function setTitle(?string $title): static;
    public function getContent(): string;
    public function setContent(string $content): static;
    public function getStart(): ?DateTimeInterface;
    public function setStart(?DateTimeInterface $start): static;
    public function getEnd(): ?DateTimeInterface;
    public function setEnd(?DateTimeInterface $end): static;
}