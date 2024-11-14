<?php
namespace Aequation\LaboBundle\Model\Trait;

use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Model\Attribute\CurrentUser;
use Aequation\LaboBundle\Model\Interface\OwnerInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;

trait Owner
{

    #[ORM\ManyToOne(targetEntity: LaboUser::class)]
    #[ORM\JoinColumn(name: 'owner_entity')]
    #[CurrentUser]
    private ?LaboUserInterface $owner = null;

    public function __construct_owner(): void
    {
        if(!($this instanceof OwnerInterface)) {
            throw new Exception('This trait must be used with the OwnerInterface');
        }
    }

    public function getOwner(): ?LaboUserInterface
    {
        return $this->owner;
    }

    public function setOwner(
        ?LaboUserInterface $owner
    ): static
    {
        $this->owner = $owner;
        return $this;
    }

}