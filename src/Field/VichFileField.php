<?php
namespace Aequation\LaboBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Vich\UploaderBundle\Form\Type\VichImageType;

class VichFileField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null)
    {
        return (new self())
            ->setProperty($propertyName)
            ->setTemplatePath('')
            ->setLabel($label)
            ->setFormType(VichImageType::class)
            ->setRequired(true)
            ->setFormTypeOptions([
                'allow_delete' => false,
            ])
            ;
    }
}
