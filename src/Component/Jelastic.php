<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Tools\Times;
use Symfony\Component\Validator\Constraints as Assert;

class Jelastic
{

    public const ENV_GROUPS = ['Aequation [AEW]','Grottes du Cerdon [GDC]','Maison Amato [MAO]','Vétérinaires','VTA','VTE','tests'];

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 32, minMessage: 'Le nom de configuration doit contenir au moins {{ limit }} caractères', maxMessage: 'Le nom de configuration doit contenir {{ limit }} caractères maximum')]
    protected ?string $name = null;

    protected ?string $displayName = null;

    #[Assert\NotBlank]
    #[Assert\Url(message: 'Ceci n\'est pas une URL valide')]
    protected ?string $homepage = null;

    #[Assert\NotBlank]
    #[Assert\Url(message: 'Ceci n\'est pas une URL valide')]
    protected ?string $baseUrl = null;

    protected ?array $envGroups = [];

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\w[\w_-]+$/', message: 'Nom invalide : utiliser uniquement des lettres ou des chiffres et ou des tirets et/ou underscore')]
    #[Assert\Length(min: 3, max: 24, minMessage: 'Le de la base de données doit contenir au moins {{ limit }} caractères', maxMessage: 'Le de la base de données doit contenir {{ limit }} caractères maximum')]
    protected ?string $dbname = 'webapp';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\w[\w_-]+$/', message: 'Nom invalide : utiliser uniquement des lettres ou des chiffres et ou des tirets et/ou underscore')]
    #[Assert\Length(min: 3, max: 24, minMessage: 'Le nom utilisateur doit contenir au moins {{ limit }} caractères', maxMessage: 'Le nom utilisateur doit contenir {{ limit }} caractères maximum')]
    protected ?string $dbuser = 'appuser';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 64, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères', maxMessage: 'Le mot de passe doit contenir {{ limit }} caractères maximum')]
    protected ?string $dbpwd = 'n78GPYul15gsd75kd54';

    protected bool $usenpm = false;

    protected bool $usemailcatcher = false;


    public function setData(
        array $data
    ): static
    {
        foreach ($data as $field => $value) {
            $setter = 'set'.ucfirst($field);
            if(method_exists($this, $setter)) $this->$setter($value);
        }
        return $this;
    }

    public function computeData(
        array $data
    ): array
    {
        $this->setData($data);
        foreach (array_keys($data) as $field) {
            $getter = 'get'.ucfirst($field);
            if(method_exists($this, $getter)) $data[$field] = $this->$getter();
        }
        return $data;
    }

    public function getId(): ?string
    {
        return empty($this->name)
            ? null
            : Strings::getSlug($this->name);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(
        ?string $name
    ): static
    {
        $this->name = $name;
        if(empty($this->displayName)) $this->setDisplayName(null);
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(
        ?string $displayName
    ): static
    {
        $this->displayName = $displayName;
        if(empty($this->displayName) && !empty($this->name)) {
            $this->displayName = $this->name.'-'.Times::getCurrentDate('m-Y');
        }
        if(!empty($this->displayName)) $this->displayName = Strings::getSlug($this->displayName);
        return $this;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(
        ?string $homepage
    ): static
    {
        $this->homepage = $homepage;
        return $this;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(
        ?string $baseUrl
    ): static
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function getEnvGroups(): array
    {
        return $this->envGroups;
    }

    public function getEnvGroupsAsString(): string
    {
        return empty($this->envGroups)
            ? '[]'
            : '["'.implode('","', $this->envGroups).'"]';
    }

    public function setEnvGroups(
        array $envGroups
    ): static
    {
        $this->envGroups = $envGroups;
        return $this;
    }

    public function getEnvGroupsChoices(): array
    {
        $choices = [];
        foreach (static::ENV_GROUPS as $group) {
            $choices[$group] = $group;
        }
        return $choices;
    }

    public function getDbname(): ?string
    {
        return $this->dbname;
    }

    public function setDbname(
        ?string $dbname
    ): static
    {
        $this->dbname = $dbname;
        return $this;
    }

    public function getDbuser(): ?string
    {
        return $this->dbuser;
    }

    public function setDbuser(
        ?string $dbuser
    ): static
    {
        $this->dbuser = $dbuser;
        return $this;
    }

    public function getDbpwd(): ?string
    {
        return $this->dbpwd;
    }

    public function setDbpwd(
        ?string $dbpwd
    ): static
    {
        $this->dbpwd = $dbpwd;
        return $this;
    }

    public function getUsenpm(): bool
    {
        return $this->usenpm;
    }

    public function setUsenpm(
        mixed $usenpm
    ): static
    {
        $this->usenpm = (bool)$usenpm;
        return $this;
    }

    public function getUsemailcatcher(): bool
    {
        return $this->usemailcatcher;
    }

    public function setUsemailcatcher(
        mixed $usemailcatcher
    ): static
    {
        $this->usemailcatcher = (bool)$usemailcatcher;
        return $this;
    }

}