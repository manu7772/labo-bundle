<?php
namespace Aequation\LaboBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class UploadedFile extends Constraint
{
    public string $message = 'Vous devez choisir un fichier SVP.';

    #[HasNamedArguments]
    public function __construct(
        public string $property,
        array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

}