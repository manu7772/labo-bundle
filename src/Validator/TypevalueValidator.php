<?php
namespace Aequation\LaboBundle\Validator;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Exception;

class TypevalueValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Typevalue) {
            throw new UnexpectedTypeException($constraint, Typevalue::class);
        }

        $entity = $this->context->getObject();
        if(empty($entity)) throw new Exception(vsprintf('Error %s() line %d: could not retrieve source object!', [__METHOD__, __LINE__]));

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        $typevalue = $propertyAccessor->getValue($entity, $constraint->typevaluefield);

        // $default_message = $constraint->getMessage();
        $message = null;
        switch ($typevalue) {
            case 'class':
                if(!class_exists($value)) {
                    $message = 'Cette classe %typevalue% n\'existe pas';
                }
                break;
            default:
                # code...
                break;
        }

        if($message) {
            $this->context
                ->buildViolation($message)
                ->setParameter('{{ typevalue }}', $value)
                ->addViolation()
                ;
        }


    }

}
