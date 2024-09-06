<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\Portrait;
use Aequation\LaboBundle\Form\Type\ImageType;

use Symfony\Component\Form\FormBuilderInterface;

class PortraitType extends ImageType
{
    public const CLASSNAME = Portrait::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

}