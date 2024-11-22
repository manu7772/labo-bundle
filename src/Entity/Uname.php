<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Repository\UnameRepository;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Model\Interface\UnameInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\UnameServiceInterface;
use Aequation\LaboBundle\Service\UnameService;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UnameRepository::class)]
#[UniqueEntity('euidofentity', message: 'Cet euid-of-entity {{ value }} est déjà utilisé !')]
// #[UniqueEntity('uname', message: 'Ce uname {{ value }} est déjà utilisé !')]
#[EA\ClassCustomService(UnameServiceInterface::class)]
class Uname extends MappSuperClassEntity implements UnameInterface
{

    public const ICON = "tabler:fingerprint";
    public const FA_ICON = "fingerprint";

    #[Serializer\Ignore]
    public readonly UnameService|AppEntityManagerInterface $_service;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 3, minMessage: 'Uname doit contenir au moins {{ min }} lettres')]
    private string $uname;

    #[ORM\Column(length: 255, updatable: false)]
    #[Assert\NotNull]
    private string $euidofentity;

    #[Serializer\Ignore]
    public readonly AppEntityInterface $entity;


    public function __toString(): string
    {
        return $this->uname ?? parent::__toString();
    }

    public function attributeEntity(
        UnamedInterface $entity,
        string $uname = null
    ): static
    {
        if(!isset($this->entity)) {
            $this->entity = $entity;
        }
        if(!empty($uname) || !isset($this->uname)) {
            if(empty($uname)) $uname = $this->entity->getEuid();
            $this->setUname($uname);
        }
        $this->setEuidofentity($this->entity->getEuid());
        return $this;
    }

    public function getUname(): ?string
    {
        return $this->uname ?? null;
    }

    private function setUname(string $uname): static
    {
        $this->uname = $uname;
        return $this;
    }

    #[Serializer\Ignore]
    public function getEuidofentity(): ?string
    {
        return $this->euidofentity;
    }

    private function setEuidofentity(string $euidofentity): static
    {
        $this->euidofentity = $euidofentity;
        return $this;
    }
}
