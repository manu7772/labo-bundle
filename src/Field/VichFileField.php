<?php
namespace Aequation\LaboBundle\Field;

use Symfony\Contracts\Translation\TranslatableInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Vich\UploaderBundle\Form\Type\VichImageType;

class VichFileField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): FieldInterface
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
