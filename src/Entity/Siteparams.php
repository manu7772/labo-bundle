<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Entity\MappSuperClassEntity;
use Aequation\LaboBundle\Model\Interface\SiteparamsInterface;
use Aequation\LaboBundle\Model\Interface\CreatedInterface;
use Aequation\LaboBundle\Model\Trait\Created;
use Aequation\LaboBundle\Model\Attribute as EA;
use Aequation\LaboBundle\Repository\SiteparamsRepository;
use Aequation\LaboBundle\Service\Tools\Iterables;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Interface\SiteparamsServiceInterface;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Validator\Typevalue;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Exception;
use Twig\Markup;

#[ORM\Entity(repositoryClass: SiteparamsRepository::class)]
#[UniqueEntity('name', message: 'Ce nom de paramètre {{ value }} existe déjà', repositoryMethod: 'findBy')]
#[HasLifecycleCallbacks]
#[EA\ClassCustomService(SiteparamsServiceInterface::class)]
class Siteparams extends MappSuperClassEntity implements SiteparamsInterface, CreatedInterface
{

    use Created;

    public const ICON = "tabler:settings-cog";
    public const FA_ICON = "cogs";
    public const NAME_PATTERN = '/^\\w([\\w\\d][\\.\\-_]?)*\\w$/';
    public const TYPES_TYPEVALUES_EXTENDED = ['auto','class'];
    public const SERIALIZATION_PROPS = ['id','euid','name','typevalue','description','paramvalue','dispatch','classname','shortname'];

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Veuillez nommer ce paramètre.')]
    #[Assert\Regex(pattern: Siteparams::NAME_PATTERN, message: 'Le nom doit contenir au minimum 2 lettres (et commencer et finir par une lettre, contenir éventuellement des [.-_] isolés.')]
    protected ?string $name = null;

    #[ORM\Column(length: 64, updatable: true)]
    protected ?string $typevalue = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Typevalue(typevaluefield: 'typevalue')]
    protected mixed $paramvalue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column()]
    protected bool $dispatch = false;


    public function __toString(): string
    {
        return $this->name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        // if(!preg_match(static::NAME_PATTERN, $this->name)) throw new Exception(vsprintf('Error on %s line %d: le nom %s est incorrect : %s !', [__METHOD__, __LINE__, $this->name, $name]));
        return $this;
    }

    public function setDispatch(bool $dispatch): static
    {
        $this->dispatch = $dispatch;
        return $this;
    }

    public function isDispatch(): bool
    {
        return $this->dispatch;
    }

    public static function getTypevalues(): array
    {
        $typevalues = array_merge(Iterables::TYPES_TYPEVALUES, static::TYPES_TYPEVALUES_EXTENDED);
        // array_unshift($typevalues, 'auto');
        sort($typevalues);
        return $typevalues;
    }

    public static function getTypevalueChoices(): array
    {
        return array_combine(static::getTypevalues(), static::getTypevalues());
    }

    public function getTypevalue(): ?string
    {
        return $this->typevalue;
    }

    public function setTypevalue(string $typevalue): static
    {
        if(!in_array($typevalue, static::getTypevalues())) throw new Exception(vsprintf('Error on %s line %d: type of value %s is invalid. Choose in %s!', [__METHOD__, __LINE__, json_encode($typevalue), json_encode(static::getTypevalues())]));
        $this->typevalue = $typevalue;
        return $this;
    }

    // public function getParam(
    //     bool $toString = false
    // ): mixed
    // {
    //     $param = static::toParam($this->paramvalue, $this->getTypevalue());
    //     return $toString
    //         ? static::paramToString($param)
    //         : $param;
    // }

    public static function toParam(
        string $paramvalue,
        string $typevalue
    ): mixed
    {
        return Encoders::stringToType($paramvalue, $typevalue);
    }

    public function dumpParam(): Markup
    {
        return Encoders::getPrintr($this->paramvalue);
    }

    // public static function paramToString(
    //     mixed $param
    // ): Markup
    // {
    //     return Encoders::getPrintr($param);
    // }

    public function getParamvalue(): mixed
    {
        return $this->paramvalue;
    }

    public function oneStringLineParam(): Markup
    {
        return Strings::markup('['.$this->getTypevalue().'] '.json_encode($this->paramvalue));
    }

    public function setParamvalue(mixed $paramvalue): static
    {
        // if(!is_string($paramvalue)) throw new Exception(vsprintf('Error on %s line %d: string is needed as parameter, %s %s given!', [__METHOD__, __LINE__, gettype($paramvalue), json_encode($paramvalue)]));
        $this->paramvalue = $paramvalue;
        return $this;
    }

    public function setFormvalue(mixed $formvalue): static
    {
        switch ($this->typevalue) {
            case 'NULL':
                $this->paramvalue = null;
                break;
            case 'boolean':
                $this->paramvalue = (bool)$formvalue;
                break;
            case 'array':
                $this->paramvalue = Iterables::TextToArray($formvalue);
                break;
            case 'double':
                $this->paramvalue = floatval(preg_replace('/[,]/', '.', $formvalue));
                break;
            case 'integer':
                $this->paramvalue = intval($formvalue);
                break;
            default:
                $this->paramvalue = $formvalue;
                break;
        }
        return $this;
    }

    public function getFormvalue(): mixed
    {
        switch ($this->typevalue) {
            case 'array':
                return Iterables::ArrayToText($this->paramvalue);
                break;
            default:
                return $this->paramvalue;
                break;
        }
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