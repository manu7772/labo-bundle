<?php
namespace Aequation\LaboBundle\Model\Trait;

// Aequation
use Aequation\LaboBundle\Entity\Uname;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute as Serializer;
// PHP
use Exception;

trait Unamed
{

    #[ORM\OneToOne(cascade: ['persist'], orphanRemoval: true, fetch: 'LAZY')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid()]
    #[Serializer\Groups('detail')]
    #[Serializer\MaxDepth(1)]
    protected readonly Uname $uname;

    #[Serializer\Ignore]
    public ?string $_tempUname = null;

    public function __construct_unamed(): void
    {
        if(!($this instanceof UnamedInterface)) {
            throw new Exception('This trait must be used with the UnamedInterface');
        }
    }

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

    #[Serializer\Ignore]
    public function getUname(): ?Uname
    {
        if(!isset($this->uname)) $this->updateUname();
        return $this->uname;
    }

}