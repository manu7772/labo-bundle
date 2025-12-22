<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\MappSuperClassEntity;
use Aequation\LaboBundle\Model\Interface\CrudvoterInterface;
use Aequation\LaboBundle\Model\Interface\CreatedInterface;
use Aequation\LaboBundle\Model\Trait\Created;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Model\Trait\Unamed;
use Aequation\LaboBundle\Repository\CrudvoterRepository;
use Aequation\LaboBundle\Service\CrudvoterService;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\CrudvoterServiceInterface;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: CrudvoterRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[EA\ClassCustomService(CrudvoterServiceInterface::class)]
class Crudvoter extends MappSuperClassEntity implements CrudvoterInterface, CreatedInterface, UnamedInterface
{

    use Created, Unamed;

    public const ICON = "tabler:lock-cog";
    public const FA_ICON = "user-lock";

    #[Serializer\Ignore]
    public readonly AppEntityManagerInterface $_service;

    #[ORM\Column(length: 255)]
    protected ?string $voterclass = null;

    #[ORM\Column(length: 255)]
    protected ?string $entityclass = null;

    #[ORM\Column(length: 128)]
    protected ?string $entityshort = null;

    #[ORM\Column(nullable: true)]
    protected ?int $entity = null;

    #[ORM\Column(length: 24, nullable: true)]
    protected ?string $firewall = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $attribute = null;

    #[ORM\Column(nullable: true)]
    protected ?array $users = null;

    #[ORM\Column(type: Types::TEXT)]
    protected ?string $voter = null;


    public function getVoterclass(): ?string
    {
        return $this->voterclass;
    }

    public function setVoterclass(string $voterclass): static
    {
        $this->voterclass = $voterclass;

        return $this;
    }

    public function getEntityclass(): ?string
    {
        return $this->entityclass;
    }

    public function setEntityclass(string $entityclass): static
    {
        $this->entityclass = $entityclass;

        return $this;
    }

    public function getEntityshort(): ?string
    {
        return $this->entityshort;
    }

    public function setEntityshort(string $entityshort): static
    {
        $this->entityshort = $entityshort;

        return $this;
    }

    public function getEntity(): ?int
    {
        return $this->entity;
    }

    public function setEntity(?int $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    public function getFirewall(): ?string
    {
        return $this->firewall;
    }

    public function setFirewall(?string $firewall): static
    {
        $this->firewall = $firewall;

        return $this;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    public function setAttribute(string $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getUsers(): ?array
    {
        return $this->users;
    }

    public function setUsers(?array $users): static
    {
        $this->users = $users;

        return $this;
    }

    public function getVoter(): ?string
    {
        return $this->voter;
    }

    public function setVoter(string $voter): static
    {
        $this->voter = $voter;

        return $this;
    }
}