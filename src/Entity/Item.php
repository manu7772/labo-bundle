<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Model\Interface\CreatedInterface;
use Aequation\LaboBundle\Model\Trait\Created;
use Aequation\LaboBundle\Model\Trait\Serializable;
use Aequation\LaboBundle\Repository\ItemRepository;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
use Aequation\LaboBundle\Model\Interface\ItemInterface;
use Aequation\LaboBundle\Model\Interface\OwnerInterface;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Model\Trait\Enabled;
use Aequation\LaboBundle\Model\Trait\Owner;
use Aequation\LaboBundle\Model\Trait\Unamed;
use Aequation\LaboBundle\Service\Interface\ItemServiceInterface;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
#[EA\ClassCustomService(ItemServiceInterface::class)]
abstract class Item extends MappSuperClassEntity implements ItemInterface, CreatedInterface, EnabledInterface, UnamedInterface, OwnerInterface
{
    use Created, Enabled, Owner, Serializable, Unamed;

    public const ICON = 'tabler:file';
    public const FA_ICON = 'file';
    public const SERIALIZATION_PROPS = ['id','euid','name','classname','shortname'];


    #[ORM\Column(length: 255)]
    #[Serializer\Groups('index')]
    #[Assert\NotBlank(message: 'Vous devez renseigner un nom')]
    protected ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Ecollection::class, inversedBy: 'items', fetch: 'EXTRA_LAZY')]
    #[Serializer\Ignore]
    protected Collection $parents;

    /**
     * @var Collection<int, Pdf>
     */
    #[ORM\OneToMany(targetEntity: Pdf::class, mappedBy: 'pdfowner', cascade: ['persist'])]
    #[Assert\Valid()]
    protected Collection $pdfiles;

    #[ORM\Column]
    protected int $orderitem = 0;

    public function __construct()
    {
        parent::__construct();
        $this->parents = new ArrayCollection();
        $this->pdfiles = new ArrayCollection();
    }

    public function __clone()
    {
        parent::__clone();
        $this->name .= ' - copie'.rand(1000, 9999);
        $this->removeParents();
    }

    public function __toString(): string
    {
        return empty($this->name ?? null) ? parent::__toString() : $this->name;
    }

    public function getOrderitem(): int
    {
        return $this->orderitem;
    }

    public function setOrderitem(int $orderitem): static
    {
        $this->orderitem = $orderitem;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    #[Serializer\Ignore]
    public function addParent(EcollectionInterface $parent): static
    {
        if($this->_isModel() || $parent->_isModel()) {
            // Cannot add parent to model
            return $this;
        }
        if($parent === $this) {
            // Failed to add parent
            throw new Exception(vsprintf('Error %s line %d: An item cannot be parent of itself!', [__METHOD__, __LINE__]));
            $this->removeParent($parent);
            return $this;
        }
        if(!$this->hasParent($parent)) {
            $this->parents->add($parent);
        }
        if(!$parent->hasItem($this)) {
            // dump('***** Adding parent "'.$parent.'" to '.$this->__toString().'...');
            if(!$parent->addItem($this)) {
                // dump('***** Failed to add parent "'.$parent.'" to '.$this->__toString().'...');
                // Failed to add parent
                $this->removeParent($parent);
                $parent->removeItem($this);
            }
        }
        return $this;
    }

    #[Serializer\Ignore]
    public function getParents(): Collection
    {
        return $this->parents;
    }

    #[Serializer\Ignore]
    public function hasParent(
        EcollectionInterface $parent
    ): bool
    {
        return $this->parents->contains($parent);
    }

    #[Serializer\Ignore]
    public function removeParent(
        EcollectionInterface $parent
    ): static
    {
        $this->parents->removeElement($parent);
        if($parent->hasItem($this)) $parent->removeItem($this);
        return $this;
    }

    #[Serializer\Ignore]
    public function removeParents(): static
    {
        foreach ($this->parents->toArray() as $parent) {
            $this->removeParent($parent);
        }
        return $this;
    }

    /**
     * @return Collection<int, Pdf>
     */
    public function getPdfiles(): Collection
    {
        return $this->pdfiles;
    }

    public function addPdfile(Pdf $pdfile): static
    {
        if (!$this->pdfiles->contains($pdfile)) {
            $this->pdfiles->add($pdfile);
            $pdfile->setPdfowner($this);
        }

        return $this;
    }

    public function removePdfile(Pdf $pdfile): static
    {
        if ($this->pdfiles->removeElement($pdfile)) {
            // set the owning side to null (unless already changed)
            if ($pdfile->getPdfowner() === $this) {
                $pdfile->setPdfowner(null);
            }
        }

        return $this;
    }

}