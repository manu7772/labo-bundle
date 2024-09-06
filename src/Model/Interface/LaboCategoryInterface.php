<?php
namespace Aequation\LaboBundle\Model\Interface;

interface LaboCategoryInterface extends AppEntityInterface
{

    public const DEFAULT_TYPE = 'default';

    // name
    public function getName(): string;
    public function setName(string $name): static;
    // slug
    public function getSlug(): ?string;
    public function setSlug(string $slug): static;
    // type
    public function getType(): string;
    public function setType(string $type): static;

}