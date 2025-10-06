<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;
use Aequation\LaboBundle\Repository\LaboRelinkRepository;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Attribute\RelationOrder;
use Aequation\LaboBundle\Model\Final\FinalAddresslinkInterface;
use Aequation\LaboBundle\Model\Final\FinalCategoryInterface;
use Aequation\LaboBundle\Model\Final\FinalEmailinkInterface;
use Aequation\LaboBundle\Model\Final\FinalPhonelinkInterface;
use Aequation\LaboBundle\Model\Final\FinalUrlinkInterface;
use Aequation\LaboBundle\Model\Interface\PreferedInterface;
use Aequation\LaboBundle\Service\Interface\LaboRelinkServiceInterface;
// Symfony
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: LaboRelinkRepository::class)]
#[EA\ClassCustomService(LaboRelinkServiceInterface::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[ORM\HasLifecycleCallbacks]
// #[UniqueEntity('name', message: 'Ce nom {{ value }} existe déjà', repositoryMethod: 'findBy')]
abstract class LaboRelink extends Item implements LaboRelinkInterface
{

    public const ICON = 'tabler:link';
    public const FA_ICON = 'link';
    public const ITEMS_ACCEPT = [
        'categorys' => [FinalCategoryInterface::class],
        // 'relinks' => [LaboRelinkInterface::class],
    ];
    /**
     * @see https://www.w3schools.com/tags/att_a_target.asp 
     * <a target="_blank|_self|_parent|_top|framename">
     */
    public const TARGETS = [
        'Même page' => '_self',
        'Nouvel onglet' => '_blank',
    ];
    public const RELINK_TYPES = [
        'Url' => 'URL',
        'Adresse' => 'ADDRESS',
        'Email' => 'EMAIL',
        'Téléphone' => 'PHONE',
        'Vidéo' => 'VIDEO',
    ];
    public const RELINK_TYPE = null;

    public function __toString(): string
    {
        return (string)$this->getMainlink();
    }

    /**
     * Main link, regarding the static::RELINK_TYPE
     * - URL: type url or route
     * - ADDRESS: type address
     * - EMAIL: type email
     * - PHONE: type phone
     */
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[Serializer\Groups('index')]
    protected ?string $mainlink = null;

    #[ORM\Column]
    #[Serializer\Groups('index')]
    protected bool $prefered = false;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups('index')]
    protected ?array $params = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Serializer\Ignore]
    protected ?string $target = null;

    #[ORM\Column]
    #[Serializer\Groups('index')]
    protected bool $turboenabled = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Serializer\Groups('index')]
    protected ?string $linktitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $subtype = null;


    /**
     * @var Collection<int, FinalCategoryInterface>
     */
    #[ORM\ManyToMany(targetEntity: FinalCategoryInterface::class)]
    #[RelationOrder()]
    #[Serializer\Groups('detail')]
    protected Collection $categorys;

    public function __construct()
    {
        if(!in_array(static::RELINK_TYPE, static::RELINK_TYPES)) throw new \Exception(vsprintf('Error %s line %d: static::RELINK_TYPE is invalid. Should be one of these: %s!', [__METHOD__, __LINE__, implode(', ', static::RELINK_TYPES)]));
        parent::__construct();
        $this->categorys = new ArrayCollection();
        // $this->relinks = new ArrayCollection();
        $targets = static::TARGETS;
        $this->target = reset($targets);
    }

    public function __clone()
    {
        parent::__clone();
        $this->prefered = false;
    }

    #[Serializer\Groups('detail')]
    public function getALink(
        ?int $referenceType = Router::ABSOLUTE_PATH
    ): ?string
    {
        switch ($this->getRelinkType()) {
            case 'URL':
                /** @var FinalUrlinkInterface $this */
                if($this->isUrl()) {
                    return $this->mainlink;
                } else if($this->isRoute()) {
                    return $this->_service->getAppService()->getUrlIfExists($this->mainlink, $this->params, $referenceType);
                }
                break;
            case 'ADDRESS':
                /** @var FinalAddresslinkInterface $this */
                return $this->getMaplink();
                break;
            case 'EMAIL':
                /** @var FinalEmailinkInterface $this */
                'mailto:'.$this->mainlink;
                break;
            case 'PHONE':
                /** @var FinalPhonelinkInterface $this */
                'tel:'.preg_replace('/[\\s]/', '', $this->mainlink);
                break;
        }
        return null;
    }

    public function isUrl(): bool
    {
        return $this->getRelinkType() === 'URL' && preg_match('/^https?:\/\//', $this->mainlink);
    }

    public function isRoute(): bool
    {
        return $this->getRelinkType() === 'URL' && !!$this->isUrl();
    }

    public function isAddress(): bool
    {
        return $this->getRelinkType() === 'ADDRESS';
    }

    public function isEmail(): bool
    {
        return $this->getRelinkType() === 'EMAIL';
    }

    public function isPhone(): bool
    {
        return $this->getRelinkType() === 'PHONE';
    }

    #[Serializer\Groups('index')]
    public function getRelinkType(): ?string
    {
        return static::RELINK_TYPE;
    }

    #[Serializer\Ignore]
    public function getRelinkTypeChoices(): array
    {
        return static::RELINK_TYPES;
    }

    public function getMainlink(): ?string
    {
        return $this->mainlink;
    }

    public function setMainlink(?string $mainlink): static
    {
        $this->mainlink = $mainlink;
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

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(?array $params): static
    {
        $this->params = $params;
        return $this;
    }

    #[Serializer\Ignore]
    public function getTargetChoices(): array
    {
        return static::TARGETS;
    }

    #[Serializer\Groups('detail')]
    public function getTarget(): ?string
    {
        if($this->isUrl() || $this->isRoute()) {
            return $this->target ?? '_self';
        }
        return null;
    }

    /**
     * Get target (over attribute) as "_self" if URL is website domain, or as "_blank" if URL is external
     * 
     * @return string|null
     */
    #[Serializer\Groups('detail')]
    public function getLogicTarget(): ?string
    {
        if($this->isRoute()) return '_self';
        return $this->isUrl() ? $this->getTarget() : null;
    }

    public function setTarget(?string $target): static
    {
        $this->target = in_array($target, static::TARGETS) ? $target : null;
        return $this;
    }

    // public function getParentrelink(): ?static
    // {
    //     return $this->parentrelink;
    // }

    // public function setParentrelink(?LaboRelinkInterface $parentrelink): static
    // {
    //     $this->parentrelink = $parentrelink;
    //     return $this;
    // }

    // /**
    //  * @return Collection<int, LaboRelinkInterface>
    //  */
    // public function getRelinks(): Collection
    // {
    //     return $this->relinks;
    // }

    // public function addRelink(LaboRelinkInterface $child): static
    // {
    //     if (empty($this->parentrelink) && !$this->relinks->contains($child)) {
    //         $this->relinks->add($child);
    //         $child->setParentrelink($this);
    //     }
    //     return $this;
    // }

    // public function removeRelink(LaboRelinkInterface $child): static
    // {
    //     if ($this->relinks->removeElement($child)) {
    //         // set the owning side to null (unless already changed)
    //         if ($child->getParentrelink() === $this) {
    //             $child->setParentrelink(null);
    //         }
    //     }
    //     return $this;
    // }

    public function setTurboenabled(
        bool $turboenabled = true
    ): static
    {
        $this->turboenabled = $turboenabled;
        return $this;
    }

    public function isTurboenabled(): bool
    {
        return $this->turboenabled;
    }

    public function getLinktitle(): ?string
    {
        return $this->linktitle;
    }

    public function setLinktitle(?string $linktitle): static
    {
        $this->linktitle = $linktitle;
        return $this;
    }

    // #[ORM\PrePersist]
    // #[ORM\PreUpdate]
    // public function updateLinkTitle(): static
    // {
    //     if(empty($this->linktitle)) $this->linktitle = $this->title;
    //     $this->linktitle = trim($this->linktitle);
    //     return $this;
    // }

    /**
     * @return Collection<int, FinalCategoryInterface>
     */
    public function getCategorys(): Collection
    {
        return $this->categorys;
    }

    public function addCategory(FinalCategoryInterface $category): static
    {
        if (!$this->categorys->contains($category)) {
            $this->categorys->add($category);
        }
        return $this;
    }

    public function removeCategory(FinalCategoryInterface $category): static
    {
        $this->categorys->removeElement($category);
        return $this;
    }

    public function removeCategorys(): static
    {
        foreach ($this->categorys as $category) {
            /** @var FinalCategoryInterface $category */
            $this->removeCategory($category);
        }
        return $this;
    }

    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    public function setSubtype(?string $subtype): static
    {
        $this->subtype = $subtype;
        return $this;
    }

}
