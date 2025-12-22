<?php
namespace Aequation\LaboBundle\Model\Trait;

use Aequation\LaboBundle\Model\Interface\CreatedInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute as Serializer;
use Doctrine\ORM\EntityManager;
// PHP
use DateTimeImmutable;
use DateTimeZone;
use Exception;

trait Created
{

    #[ORM\Column(nullable: false)]
    #[Assert\NotNull(message: 'Le createdAt ne peut pas être nul')]
    #[Serializer\Groups('detail')]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups('detail')]
    protected ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le timezone ne peut pas être nul')]
    #[Serializer\Groups('detail')]
    protected ?string $timezone = null;

    public function __construct_created(): void
    {
        if(!($this instanceof CreatedInterface)) {
            throw new Exception('This trait must be used with the CreatedInterface');
        }
        $this->updateCreatedAt();
        $this->setTimezone('Europe/Paris');
    }

    public function __clone_created(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    public function getLastActionAt(): ?DateTimeImmutable
    {
        return $this->updatedAt ?? $this->createdAt;
    }

    /**
     * Returns if last action on this entity is before the given $date
     * @param DateTimeImmutable|string $date
     * @return boolean
     */
    public function compareLastAction(
        DateTimeImmutable|string $date,
    ): bool
    {
        if(is_string($date)) $date = new DateTimeImmutable($date);
        $compar = $this->getLastActionAt();
        return empty($compar) || $date > $compar;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateUpdatedAt(?LifecycleEventArgs $args = null): static
    {
        /** @var EntityManager $em */
        $em = $args?->getObjectManager();
        $changeSet = $em?->getUnitOfWork()->getEntityChangeSet($this) ?? [];
        if(isset($changeSet['updatedAt'])) {
            return $this;
        }
        $this->setUpdatedAt();
        return $this;
    }

    public function setUpdatedAt(
        ?DateTimeImmutable $updatedAt = null
    ): static
    {
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function updateCreatedAt(?LifecycleEventArgs $args = null): static
    {
        $this->setCreatedAt();
        return $this;
    }

    public function setCreatedAt(
        ?DateTimeImmutable $createdAt = null
    ): static
    {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        return $this;
    }

    public function getDateTimezone(): ?DateTimeZone
    {
        return new DateTimeZone($this->timezone);
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }


}