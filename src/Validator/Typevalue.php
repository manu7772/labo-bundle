<?php
namespace Aequation\LaboBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * @see https://symfony.com/doc/current/validation/custom_constraint.html
 */
#[Attribute]
class Typevalue extends Constraint
{
    public string $message = 'La valeur est invalide';

    #[HasNamedArguments]
    public function __construct(
        public string $typevaluefield,
        array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    // public function getTypevaluefield(): string
    // {
    //     return $this->typevaluefield;
    // }

    public function getMessage(): string
    {
        return $this->message;
    }

}