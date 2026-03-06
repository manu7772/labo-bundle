<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Model\Final\FinalAddresslinkInterface;
use Aequation\LaboBundle\Model\Final\FinalEntrepriseInterface;
use Aequation\LaboBundle\Model\Trait\Screenable;
use Aequation\LaboBundle\Model\Final\FinalUserInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


abstract class LaboEntreprise extends LaboUser implements FinalEntrepriseInterface
{

    use Screenable;

    public const ICON = "tabler:building-factory-2";
    public const FA_ICON = "industry";

    /**
     * @var Collection<int, FinalUserInterface>
     */
    #[ORM\ManyToMany(targetEntity: FinalUserInterface::class, inversedBy: 'entreprises')]
    protected Collection $members;

    #[ORM\Column]
    protected bool $prefered = false;

    public function __construct()
    {
        parent::__construct();
        $this->members = new ArrayCollection();
    }

    public function __clone()
    {
        parent::__clone();
        $this->prefered = false;
    }

    public function getName(): string
    {
        return $this->getFirstname();
    }

    /**
     * @return Collection<int, FinalUserInterface>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(FinalUserInterface $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }
        if(!$member->hasEntreprise($this)) {
            $member->addEntreprise($this);
        }
        return $this;
    }

    public function hasMember(?FinalUserInterface $member = null): bool
    {
        return $member instanceof FinalUserInterface
            ? $this->members->contains($member)
            : !$this->members->isEmpty()
            ;
    }

    public function removeMember(FinalUserInterface $member): static
    {
        if($this->members->removeElement($member) && $member->hasEntreprise($this)) {
            $member->removeEntreprise($this);
        }
        return $this;
    }

    public function isPrefered(): bool
    {
        return $this->prefered;
    }

    public function setPrefered(bool $prefered): static
    {
        $this->prefered = $prefered;
        return $this;
    }

    // Override laborelinks

    public function getAddress(bool $anyway = true): ?FinalAddresslinkInterface
    {
        return $this->getMainAddress($anyway);
    }

    public function getTelephon(bool $anyway = true): ?FinalAddresslinkInterface
    {
        return $this->getMainPhone($anyway);
    }

}