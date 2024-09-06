<?php
namespace Aequation\LaboBundle\Model\Trait;

use Aequation\LaboBundle\Entity\Uname;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute as Serializer;

trait Unamed
{

    #[ORM\OneToOne(cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid()]
    #[Serializer\Ignore]
    protected readonly Uname $uname;

    public ?string $_tempUname = null;

    // #[AppEvent(AppEvent::beforePrePersist)]
    public function autoUpdateUname(): static
    {
        return $this->updateUname();
    }

    public function __clone_unamed(): void
    {
        if(!empty($this->_tempUname)) $this->_tempUname = $this->_tempUname.' - copie'.rand(1000, 9999);
        $this->updateUname();
    }

    public function updateUname(
        string $uname = null
    ): static
    {
        if(!isset($this->uname) || $this->_isClone()) $this->uname = $this->_service->getNew(Uname::class);
        if(empty($uname)) $uname = empty($this->_tempUname) ? null : $this->_tempUname;
        $this->_tempUname = $uname;
        $this->uname->attributeEntity($this, $uname);
        return $this;
    }

    public function getUname(): ?Uname
    {
        if(!isset($this->uname)) $this->updateUname();
        return $this->uname;
    }

}