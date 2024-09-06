<?php
namespace Aequation\LaboBundle\Validator;

use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Tools\Emails;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UserExistsValidator extends ConstraintValidator
{

    public function __construct(
        protected ?LaboUserServiceInterface $userService,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UserExists) {
            throw new UnexpectedTypeException($constraint, UserExists::class);
        }

        // $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        // $property_value = $propertyAccessor->getValue($value, $constraint->property);

        if(Emails::isEmailValid($value)) {
            $test = $constraint->negative
                ? $this->userService->userExists($value, $constraint->filterContext)
                : !$this->userService->userExists($value, $constraint->filterContext);

            if($test) {
                $this->context
                    ->buildViolation($constraint->getMessage())
                    ->setParameter('{{ email }}', $value)
                    ->addViolation()
                    ;
            }
        }

    }

}
