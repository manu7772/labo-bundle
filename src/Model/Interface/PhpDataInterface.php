<?php
namespace Aequation\LaboBundle\Model\Interface;

use DateTimeInterface;

interface PhpDataInterface
{

    public function needUpdate(): bool;
    public function get(string $name = null, mixed $default = null): mixed;
    public function set(string $name, mixed $data): static;
    public function getVersion(bool $toDatetime = false): DateTimeInterface;

}
