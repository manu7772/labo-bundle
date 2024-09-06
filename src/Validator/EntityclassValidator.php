<?php
namespace Aequation\LaboBundle\Validator;

use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntityclassValidator extends ConstraintValidator
{

    public function __construct(
        protected AppEntityManagerInterface $service,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var Entityclass $constraint */

        if (class_exists($value) && $this->service->entityExists($value)) {
            return;
        }

        // TODO: implement the validation here
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
