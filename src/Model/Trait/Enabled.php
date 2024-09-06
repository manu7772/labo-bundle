<?php
namespace Aequation\LaboBundle\Model\Trait;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Attribute as Serializer;

trait Enabled
{

    public const INIT_ENABLED_ENABLED = true;
    public const INIT_ENABLED_SOFTDELETED = false;

    #[ORM\Column]
    #[Serializer\Groups(['index'])]
    private bool $enabled = true;

    #[ORM\Column]
    #[Serializer\Groups(['index'])]
    private bool $softdeleted = false;

    public function __construct_enabled(): void
    {
        $this->enabled = static::INIT_ENABLED_ENABLED;
        $this->softdeleted = static::INIT_ENABLED_SOFTDELETED;
    }

    public function __clone_enabled(): void
    {
        if($this->isSoftdeleted()) throw new Exception(vsprintf('Error %s line %d: can not clone softdeleted entity %s', [__METHOD__, __LINE__, static::class]));
        // nothing to do here...
    }

    #[Serializer\Groups(['index'])]
    public function isActive(): bool
    {
        return $this->isEnabled() && !$this->isSoftdeleted();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isDisabled(): bool
    {
        return !$this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isSoftdeleted(): bool
    {
        return $this->softdeleted;
    }

    public function setSoftdeleted(bool $softdeleted): static
    {
        $this->softdeleted = $softdeleted;

        return $this;
    }

}