<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\MappSuperClassEntity;
use Aequation\LaboBundle\Model\Interface\LaboCategoryInterface;
use Aequation\LaboBundle\Model\Interface\CreatedInterface;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Model\Attribute\Slugable;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Model\Trait\Created;
use Aequation\LaboBundle\Model\Trait\Slug;
use Aequation\LaboBundle\Model\Trait\Unamed;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\LaboCategoryServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;

// #[EA\ClassCustomService(LaboCategoryServiceInterface::class)]
#[MappedSuperclass]
#[UniqueEntity(fields: ['name','type'], message: 'Cette catégorie {{ value }} existe déjà pour ce type')]
#[UniqueEntity('slug', message: 'Ce slug {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[HasLifecycleCallbacks]
#[Slugable(property: 'name')]
abstract class LaboCategory extends MappSuperClassEntity implements LaboCategoryInterface, CreatedInterface, SlugInterface, UnamedInterface
{

    use Created, Slug, Unamed;

    public const ICON = "tabler:clipboard-list";
    public const FA_ICON = "clipboard-list";

    #[Serializer\Ignore]
    public readonly AppEntityManagerInterface|LaboCategoryServiceInterface $_service;

    #[ORM\Column(nullable: false)]
    #[Serializer\Groups('index')]
    protected ?string $name = null;

    #[ORM\Column(updatable: false, nullable: false)]
    #[Serializer\Groups('detail')]
    protected ?string $type;
    #[Serializer\Ignore]
    protected ?array $categoryTypeChoices;

    #[ORM\Column(length: 64, nullable: true)]
    #[Serializer\Groups('detail')]
    protected ?string $description = null;


    public function __construct()
    {
        parent::__construct();
        $this->type = static::DEFAULT_TYPE;
    }

    public function __toString(): string
    {
        return (string)$this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    #[Serializer\Ignore]
    public function getTypeChoices(): array
    {
        return $this->categoryTypeChoices ??= $this->_service->getCategoryTypeChoices(true);
    }

    /**
     * Get list of available types
     * 
     * Returns array of classname => shortname
     * 
     * @return array
     */
    #[Serializer\Ignore]
    public function getAvailableTypes(): array
    {
        $types = [];
        foreach ($this->getTypeChoices() as $classname => $values) {
            $types[$classname] = Classes::getShortname($values);
        }
        return $types;
    }

    public function getType(): string
    {
        return $this->type;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function checkType(
        PrePersistEventArgs|PreUpdateEventArgs $event
    ): bool
    {
        $exists = $this->_service->entityExists($this->type, true, false);
        if(!$exists && $this->_service->isDev()) {
            throw new Exception(vsprintf('Error %s line %d: type entity %s does not exist!', [__METHOD__, __LINE__, $this->type]));
        }
        return $exists;
    }

    #[Serializer\Groups('index')]
    public function getShorttype(): string
    {
        return Classes::getShortname($this->type);
    }

    #[Serializer\Ignore]
    public function getTypeAsHtml(
        bool $icon = true,
        bool $classname = false
    ): string
    {
        // if(!in_array($this->type, [static::DEFAULT_TYPE && !$this->_service->entityExists($this->type, true, false)])) {
        //     throw new Exception(vsprintf('Error %s line %d: entity %s does not exist!', [__METHOD__, __LINE__, $this->type]));
        // }
        return $this->_service::getEntityNameAsHtml($this->type, $icon, $classname);
    }

    #[Serializer\Ignore]
    public function getLongTypeAsHtml(): string
    {
        return $this->_service::getEntityNameAsHtml($this->type, true, true);
    }

    public function setType(string $type): static
    {
        $availables = [];
        foreach ($this->_service->getCategoryTypeChoices(false) as $classname => $values) {
            $availables[$classname] = Classes::getShortname($classname);
        }
        if(!array_key_exists($type, $availables)) {
            $memtype = $type;
            if(false === ($type = array_search($type, $availables))) {
                throw new Exception(vsprintf('Error %s line %d: type "%s" not found in this list: %s!', [__METHOD__, __LINE__, $memtype, json_encode($availables)]));
            }
        }
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }


}