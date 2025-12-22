<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\Picture;

use Symfony\Component\Form\FormBuilderInterface;

class PictureType extends ImageType
{
    public const CLASSNAME = Picture::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

}