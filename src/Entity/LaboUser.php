<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\MappSuperClassEntity;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;
use Aequation\LaboBundle\Model\Trait\Enabled;
use Aequation\LaboBundle\Model\Trait\Created;
use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Entity\Portrait;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Attribute\RelationOrder;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Model\Trait\Unamed;
use Aequation\LaboBundle\Repository\LaboUserRepository;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\AppRoleHierarchyInterface;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Service\LaboUserService;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Final\FinalAddresslinkInterface;
use Aequation\LaboBundle\Model\Final\FinalCategoryInterface;
use Aequation\LaboBundle\Model\Final\FinalEmailinkInterface;
use Aequation\LaboBundle\Model\Final\FinalPhonelinkInterface;
use Aequation\LaboBundle\Model\Final\FinalUrlinkInterface;
use Aequation\LaboBundle\Model\Interface\CreatedInterface;
use Aequation\LaboBundle\Model\Interface\EnabledInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
// PHP
use DateInterval;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: LaboUserRepository::class)]
// #[EA\ClassCustomService(LaboUserServiceInterface::class)]
#[ORM\DiscriminatorColumn(name: "class_name", type: "string")]
#[ORM\InheritanceType('JOINED')]
#[UniqueEntity(['email','classname'], message: 'Cet email {{ value }} est déjà utilisé !')]
#[ORM\HasLifecycleCallbacks]
abstract class LaboUser extends MappSuperClassEntity implements LaboUserInterface, EquatableInterface, ImageOwnerInterface, UnamedInterface, EnabledInterface, CreatedInterface
{

    use Enabled, Created, Unamed;

    public const ICON = "tabler:user-filled";
    public const FA_ICON = "user";
    public const SERIALIZATION_PROPS = ['id','email'];
    // public const SERIALIZATION_PROPS = ['id','euid','firstname','lastname','darkmode','expiresAt','isVerified','lastLogin','email','roles','classname','shortname'];
    public const ITEMS_ACCEPT = [
        'categorys' => [FinalCategoryInterface::class],
        'addresses' => [FinalAddresslinkInterface::class],
        'emails' => [FinalEmailinkInterface::class],
        'phones' => [FinalPhonelinkInterface::class],
    ];

    #[Serializer\Ignore]
    public readonly LaboUserService|AppEntityManagerInterface $_service;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email(message: 'L\'adresse {{ value }} est invalide')]
    #[Assert\NotNull(message: 'Vous devez renseigner une adresse email.')]
    protected ?string $email = null;

    #[ORM\Column(type: Types::JSON)]
    #[Serializer\Groups('detail')]
    protected array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: Types::STRING)]
    #[Serializer\Ignore]
    protected ?string $password = null;

    /** 
     * @see https://symfony.com/doc/current/reference/constraints/PasswordStrength.html
     * @see https://github.com/symfony/symfony/blob/7.0/src/Symfony/Component/Validator/Constraints/PasswordStrength.php
     */
    #[SecurityAssert\UserPassword(message: 'Votre mot de passe n\'est pas valable', groups: ['registration'])]
    // #[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM, message: 'Ce mot de passe n\'est pas assez sécurisé')]
    #[Serializer\Ignore]
    protected ?string $plainPassword = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups('index')]
    protected ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups('detail')]
    protected ?string $lastname = null;

    #[ORM\Column]
    #[Serializer\Groups('index')]
    protected bool $darkmode = true;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups('detail')]
    protected ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    #[Serializer\Groups('index')]
    protected ?bool $isVerified = false;

    #[Serializer\Ignore]
    protected ?AppRoleHierarchyInterface $roleHierarchy = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups('detail')]
    protected ?DateTimeImmutable $lastLogin = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid()]
    #[Serializer\Ignore]
    protected ?Portrait $portrait = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups('index')]
    protected ?string $fonction = null;

    /**
     * @var Collection<int, FinalCategoryInterface>
     */
    #[ORM\ManyToMany(targetEntity: FinalCategoryInterface::class)]
    #[RelationOrder()]
    #[Serializer\Ignore]
    protected Collection $categorys;

    #[ORM\ManyToMany(targetEntity: FinalUrlinkInterface::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[RelationOrder()]
    #[Serializer\Ignore]
    protected Collection $relinks;

    #[ORM\ManyToMany(targetEntity: FinalAddresslinkInterface::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[RelationOrder()]
    #[Serializer\Ignore]
    protected Collection $addresses;

    #[ORM\ManyToMany(targetEntity: FinalEmailinkInterface::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[RelationOrder()]
    #[Serializer\Ignore]
    protected Collection $emails;

    #[ORM\ManyToMany(targetEntity: FinalPhonelinkInterface::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[RelationOrder()]
    #[Serializer\Ignore]
    protected Collection $phones;

    public function __construct()
    {
        parent::__construct();
        $this->categorys = new ArrayCollection();
        $this->relinks = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->phones = new ArrayCollection();
    }

    public function isEqualTo(UserInterface $user): bool
    {
        /** @var LaboUserInterface $user */
        return
            $user->getEmail() === $this->getEmail()
            && $user->getId() === $this->getId()
            ;
    }

    public function __clone_entity(): void
    {
        parent::__clone_entity();
        $this->removePortrait();
        $this->email = null;
    }

    public function __toString(): string
    {
        return $this->getCivilName().' ['.$this->email.']';
        // $string = (string)$this->firstname ?? (string)$this->email;
        // return empty($string) ? parent::__toString() : $string;
    }

    // public function getSerializableAttributes(): Iterable
    // {
    //     $attrs = iterator_to_array(parent::getSerializableAttributes());
    //     return array_unique(array_merge($attrs, ['id', 'email', 'roles', 'password', 'firstname', 'lastname', 'darkmode', 'isVerified', 'lastLogin', 'createdAt', 'updatedAt', 'enabled', 'softdeleted']));
    // }

    public function canLogin(): bool
    {
        return $this->isEnabled() && !$this->isSoftdeleted() && !$this->isExpired();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = trim($email);
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Get name for Address $name
     * @return string
     */
    public function getEmailName(): string
    {
        return $this->getCivilName();
    }

    /**
     * Get print name
     * @return string
     */
    public function getCivilName(): string
    {
        $name = trim(str_replace(["\n", "\r"], '', $this->firstname.' '.$this->lastname));
        if(empty($name)) $name = $this->email;
        return $name;
    }

    public function setRoleHierarchy(AppRoleHierarchyInterface $roleHierarchy): void
    {
        $this->roleHierarchy = $roleHierarchy;
        $this->sortRoles();
    }

    public function getReachableRoles(): array
    {
        $roles = $this->roleHierarchy->getReachableRoleNames($this->getRoles());
        $this->roleHierarchy->sortRoles($roles);
        return $roles;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function sortRoles(): void
    {
        $this->roleHierarchy->sortRoles($this->roles, false); // false is IMPORTANT!!!
    }

    public function getHigherRole(): string
    {
        $role = $this->roleHierarchy->getHigherRole($this->roles);
        return $role
            ? $role
            : static::ROLE_USER;
    }

    public function getLowerRole(): string
    {
        $role = $this->roleHierarchy->getLowerRole($this->roles);
        return $role
            ? $role
            : static::ROLE_USER;
    }

    public function getInferiorRoles(): array
    {
        return $this->roleHierarchy->getInferiorRoles($this->getHigherRole());
    }

    public function getRolesChoices(UserInterface $user = null): array
    {
        return $this->roleHierarchy->getRolesChoices($user ?? $this);
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        array_unshift($roles, static::ROLE_USER);
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = array_unique($roles);
        $this->sortRoles();
        return $this;
    }

    public function addRole(string $role): static
    {
        $this->roles = array_unique(array_merge($this->roles, [$role]));
        $this->sortRoles();
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function removeRole(string $role): static
    {
        $this->roles = array_filter(
            $this->roles,
            function ($in) use ($role) { return $in !== $role; }
        );
        $this->sortRoles();
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        $this->updateUpdatedAt();
        return $this;
    }

    public function autoGeneratePassword(
        int $length = 32,
        string $chars = null,
        bool $replace = true
    ): static
    {
        if(empty($this->plainPassword) || $replace) $this->plainPassword = Encoders::generatePassword($length, $chars);
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function isDarkmode(): ?bool
    {
        return $this->darkmode;
    }

    public function setDarkmode(bool $darkmode): static
    {
        $this->darkmode = $darkmode;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt instanceof DateTimeImmutable
            ? $this->expiresAt < new DateTimeImmutable()
            : false;
    }

    public function expiresIn(): ?DateInterval
    {
        return $this->expiresAt instanceof DateTimeImmutable
            ? $this->expiresAt->diff(new DateTimeImmutable())
            : null;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getLastLogin(): ?DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTimeImmutable $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function updateLastLogin(): static
    {
        $this->setLastLogin(new DateTimeImmutable('NOW'));
        return $this;
    }

    // public function removeOwnedImage(Image $portrait): static
    // {
    //     if($this->portrait === $portrait) {
    //         $this->portrait = null;
    //         // $portrait->setLinkedto(null);
    //     }
    //     return $this;
    // }

    public function getFirstImage(): ?Image
    {
        return $this->portrait;
    }

    public function getPortrait(): ?Portrait
    {
        return $this->portrait;
    }

    public function setPortrait(Portrait $portrait): static
    {
        // Remove previous portrait
        // if($this->portrait && $this->portrait !== $portrait) $this->portrait->removeLinkedto();
        // $this->portrait = $portrait->setLinkedto($this);
        $this->portrait = $portrait;
        return $this;
    }

    #[AppEvent(groups: [AppEvent::POST_SUBMIT])]
    public function onDeleteFirstImage(): static
    {
        if($this->portrait instanceof Image && $this->portrait->isDeleteImage()) {
            $this->removePortrait();
        }
        return $this;
    }

    public function removePortrait(): static
    {
        if($this->portrait instanceof Portrait) {
            // $this->portrait->removeLinkedto();
            $this->portrait = null;
        }
        return $this;
    }

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

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(?string $fonction): static
    {
        $this->fonction = $fonction;
        return $this;
    }


    public function getRelinks(): Collection
    {
        return $this->relinks;
    }

    public function addRelink(FinalUrlinkInterface $relink): static
    {
        if (!$this->relinks->contains($relink)) {
            $this->relinks->add($relink);
        }
        return $this;
    }

    public function removeRelink(FinalUrlinkInterface $relink): static
    {
        $this->relinks->removeElement($relink);
        return $this;
    }

    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(FinalAddresslinkInterface $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
        }
        return $this;
    }

    public function removeAddress(FinalAddresslinkInterface $address): static
    {
        $this->addresses->removeElement($address);
        return $this;
    }

    public function getEmails(): Collection
    {
        return $this->emails;
    }

    public function addEmail(FinalEmailinkInterface $email): static
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
        }
        return $this;
    }

    public function removeEmail(FinalEmailinkInterface $email): static
    {
        $this->emails->removeElement($email);
        return $this;
    }

    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function addPhone(FinalPhonelinkInterface $phone): static
    {
        if (!$this->phones->contains($phone)) {
            $this->phones->add($phone);
        }
        return $this;
    }

    public function removePhone(FinalPhonelinkInterface $phone): static
    {
        $this->phones->removeElement($phone);
        return $this;
    }



}