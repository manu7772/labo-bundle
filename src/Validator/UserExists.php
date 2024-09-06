<?php
namespace Aequation\LaboBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * @see https://symfony.com/doc/current/validation/custom_constraint.html
 */
#[Attribute]
class UserExists extends Constraint
{
    public string $message = 'Cet utilisateur n\'existe pas';

    #[HasNamedArguments]
    public function __construct(
        public bool $negative = false,
        public bool $filterContext = true,
        array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    public function getMessage(): string{
        $this->message = $this->negative
            ? 'Cet utilisateur existe déjà'
            : 'Cet utilisateur n\'existe pas'
            ;
        return $this->message;
    }

}