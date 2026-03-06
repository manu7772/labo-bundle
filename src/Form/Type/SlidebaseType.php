<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Form\Type\ImageType;
use Aequation\LaboBundle\Entity\LaboSlidebase;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;

class SlidebaseType extends ImageType
{
    public const CLASSNAME = LaboSlidebase::class;
    public const DELETE_IMAGE = false;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

}