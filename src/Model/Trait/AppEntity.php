<?php
namespace Aequation\LaboBundle\Model\Trait;

use Aequation\LaboBundle\Component\Interface\AppEntityInfoInterface;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Model\Interface\UnamedInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Service\Tools\Iterables;
use Aequation\LaboBundle\Service\Tools\Strings;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute as Serializer;

use ReflectionClass;
use Exception;

#[UniqueEntity(fields: ['euid'], message: 'Cet EUID {{ value }} est déjà utilisé !')]
trait AppEntity
{

    public const ICON = 'tabler:question-mark';
    public const FA_ICON = 'question';
    // public const SERIALIZATION_PROPS = ['euid','classname','shortname'];

    #[ORM\Column(length: 255, updatable: false, nullable: false)]
    #[Assert\NotNull()]
    #[Serializer\Groups('index')]
    protected readonly string $euid;

    #[ORM\Column(updatable: false, nullable: false)]
    #[Assert\NotNull()]
    #[Serializer\Groups('index')]
    protected readonly string $classname;

    #[ORM\Column(length: 32, updatable: false, nullable: false)]
    #[Assert\NotNull()]
    #[Serializer\Groups('index')]
    protected readonly string $shortname;

    #[Serializer\Ignore]
    protected bool $_isClone = false;
    #[Serializer\Ignore]
    protected readonly bool $_isModel;

    #[Serializer\Ignore]
    public readonly AppEntityInfoInterface $_appManaged;
    
    #[Serializer\Ignore]
    public readonly AppEntityManagerInterface $_service;

    public function __construct_entity(): void
    {
        $rc = new ReflectionClass(static::class);
        $this->classname = $rc->getName();
        $this->euid = $this->getNewEuid();
        $this->shortname = $rc->getShortName();
        // Other constructs
        $construct_methods = array_filter(get_class_methods($this), fn($method_name) => preg_match('/^__construct_(?!entity)/', $method_name));
        foreach ($construct_methods as $method) {
            $this->$method();
        }
    }

    // #[AppEvent(groups: AppEvent::onCreate)]
    // public function test()
    // {
    //     dd($this);
    // }

    public function _isClone(): bool
    {
        return $this->_isClone;
    }

    public function _setClone(
        bool $_isClone
    ): static
    {
        $this->_isClone = $_isClone;
        return $this;
    }

    public function _isModel(): bool
    {
        return isset($this->_isModel) ? $this->_isModel : false;
    }

    public function _setModel(): static
    {
        $this->_isModel = true;
        return $this;
    }

    #[AppEvent(groups: [AppEvent::afterClone])]
    public function _removeIsClone(): static
    {
        if($this->_service->isDev() && $this->_isClone()) {
            throw new Exception(vsprintf('Error %s line %d: this %s "%s" should not be isClone status TRUE when AppEvent %s !', [__METHOD__, __LINE__, $this->getClassname(), $this, AppEvent::afterClone]));
        }
        $this->_setClone(false);
        return $this;
    }

    public function __clone_entity(): void
    {
        $this->euid = $this->getNewEuid();
        $this->_service->setManagerToEntity($this);
        // Other clones
        $clone_methods = array_filter(get_class_methods($this), fn($method_name) => preg_match('/^__clone_(?!entity)/', $method_name));
        foreach ($clone_methods as $method) {
            $this->$method();
        }
    }

    public function getEuid(): string
    {
        return $this->euid ??= $this->getNewEuid();
    }

    #[Serializer\Ignore]
    public function getUnameThenEuid(): string
    {
        if($this instanceof UnamedInterface) {
            return $this->getUname()->getUname();
        }
        return $this->getEuid();
    }

    public function defineUname(
        string $uname
    ): static
    {
        if($this instanceof UnamedInterface) {
            if(strlen($uname) < 3) throw new Exception(vsprintf('Error %s line %d: Uname for %s must have at least 3 lettres, got "%s"!', [__METHOD__, __LINE__, static::class, $uname]));
            $this->updateUname($uname);
        }
        return $this;
    }

    #[Serializer\Ignore]
    private function getNewEuid(): string
    {
        return Encoders::geUniquid($this->classname.'|');
    }

    public function getClassname(): string
    {
        if(empty($this->classname)) {
            $rc = new ReflectionClass(static::class);
            $this->classname = $rc->getName();    
        }
        return $this->classname;
    }

    /**
     * Get formated shortname
     * @param string $type
     * @return string
     */
    #[Serializer\Ignore]
    public function getShortnameFormated(
        string $type = 'camel',
    ): string
    {
        return Strings::stringFormated($this->shortname, $type);
    }

    /**
     * Get formated shortname
     * @param string $type
     * @return string
     */
    #[Serializer\Ignore]
    public function getShortnameDecorated(
        string $type = 'camel',
        string $prefix = null,
        string $suffix = null,
    ): string
    {
        if(in_array($type, ['camel','pascal','folded','snake'])) {
            $prefix = empty($prefix) ? '' : $prefix.'_';
            $suffix = empty($suffix) ? '' : '_'.$suffix;
        }
        $shortname = Strings::stringFormated($prefix.$this->shortname.$suffix, $type);
        return $shortname;
    }

    public function getShortname(
        bool $lowercase = false
    ): string
    {
        return $lowercase
            ? $this->getShortnameFormated('lower')
            : $this->shortname;
    }

    #[Serializer\Ignore]
    public static function _shortname(
        string $type,
        string $prefix = null,
        string $suffix = null
    ): string
    {
        $rc = new ReflectionClass(static::class);
        return Strings::stringFormated($prefix.$rc->getShortName().$suffix, $type);
    }

    #[Serializer\Ignore]
    public static function getIcon(bool|string $asClass = false, array|string $addClasses = ['fa-fw']): string
    {
        $icon = preg_replace('/^(fa(s|b)?-)*/', '', static::FA_ICON);
        if(!$asClass) return $icon;
        $classes = [];
        $addClasses = Iterables::toClassList($addClasses, false);
        switch ($asClass) {
            case true:
            case 'fa':
                array_unshift($classes, ['fa', 'fa-'.$icon]);
                break;
            case 'fas':
                array_unshift($classes, ['fas', 'fa-'.$icon]);
                break;
            case 'fab':
                array_unshift($classes, ['fab', 'fa-'.$icon]);
                break;
        }
        if(!empty($addClasses)) $classes = array_merge($classes, $addClasses);
        return implode(' ', array_unique($classes));
    }

    public function __setAppManaged(AppEntityInfoInterface $_appManaged): void
    {
        if(!$this->canUpdateAppManaged() && $this->_service->isDev()) {
            throw new Exception(vsprintf('Error on %s line %d: %s "%s" still app managed!.', [__METHOD__, __LINE__, $this->getShortname(), $this->__toString()]));
        }
        $this->_appManaged = $_appManaged;
        $this->_service = $this->_appManaged->manager;
        if(!$this->_appManaged->isValid() && $this->_service->isDev()) {
            throw new Exception(vsprintf('Error on %s line %d: %s "%s" app managed is invalid!.', [__METHOD__, __LINE__, $this->getShortname(), $this->__toString()]));
        }
    }

    #[Serializer\Ignore]
    public function __isAppManaged(): bool
    {
        return isset($this->_appManaged);
    }

    #[Serializer\Ignore]
    protected function canUpdateAppManaged(): bool
    {
        return !isset($this->_appManaged)
            || $this->_isClone()
            ;
    }

}