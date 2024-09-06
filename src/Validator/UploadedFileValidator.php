<?php
namespace Aequation\LaboBundle\Validator;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UploadedFileValidator extends ConstraintValidator
{

    // public function __construct(
    //     protected AppService $appService,
    // ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UploadedFile) {
            throw new UnexpectedTypeException($constraint, UploadedFile::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        // if (null === $value || '' === $value) {
        //     return;
        // }

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        $file = $propertyAccessor->getValue($value, $constraint->property);
        $id = $propertyAccessor->getValue($value, 'id');
        if($file instanceof File || !empty($id)) {
            return;
        }

        // the argument must be a string or an object implementing __toString()
        $this->context->buildViolation($constraint->message)
            // ->setParameter('{{ string }}', $value)
            ->atPath($constraint->property)
            ->addViolation();
    }
}
