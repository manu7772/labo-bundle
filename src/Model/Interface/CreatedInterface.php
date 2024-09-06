<?php
namespace Aequation\LaboBundle\Model\Interface;

use DateTimeImmutable;

interface CreatedInterface extends AppEntityInterface
{
    public function __construct_created(): void;
    public function __clone_created(): void;
    public function getUpdatedAt(): ?DateTimeImmutable;
    public function updateUpdatedAt(): static;
    public function setUpdatedAt(): static;
    public function getCreatedAt(): ?DateTimeImmutable;
    public function updateCreatedAt(): static;
    public function setCreatedAt(): static;
    public function getTimezone(): ?string;
    public function setTimezone(string $timezone): static;
}
