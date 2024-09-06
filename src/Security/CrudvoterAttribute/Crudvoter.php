<?php
namespace Aequation\LaboBundle\Security\CrudvoterAttribute;

use Aequation\LaboBundle\Security\CrudvoterAttribute\Base\BaseCrudvoter;

use UnitEnum;

class Crudvoter extends BaseCrudvoter
{
    // Context
    #[UnitEnum(['route','firewall','groupe',null])]
    public $type = null;                    // route / firewall / groupe / NULL
    public ?string $location = null;        // string for $type
    /** @var int */
    #[UnitEnum([1,2,3])]
    public int $level = 1;                  // 1..3

    // Action/Attribute
    public ?string $action = null;          // ROLE, etc.

    // Subject
    public ?string $subject = null;         // voter attribute
    public ?string $subjectid = null;       // ID of object attribute

    // Access/form params
    public ?string $getter = 'auto';        // getProperty(): mixed
    public ?string $setter = 'auto';        // setProperty(mixed $value): static
    public ?string $decisioner = 'auto';    // PropertyDecisionVoter(User $user): boolean
    public ?bool $byreference = null;       // 
    public int $index = 0;                  // Order

}