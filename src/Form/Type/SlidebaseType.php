<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Form\Type\ImageType;
use Aequation\LaboBundle\Entity\LaboSlidebase;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
// Symfony
use Symfony\Component\Form\FormBuilderInterface;

abstract class SlidebaseType extends ImageType
{
    // public const CLASSNAME = LaboSlidebase::class;
    public const DELETE_IMAGE = false;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // dump($this, $builder, $options);
        parent::buildForm($builder, $options);
        $builder->remove('name');
        $builder->remove('imagefilter');
    }

}