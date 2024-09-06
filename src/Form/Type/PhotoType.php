<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\Photo;

use Symfony\Component\Form\FormBuilderInterface;

class PhotoType extends ImageType
{
    public const CLASSNAME = Photo::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

}