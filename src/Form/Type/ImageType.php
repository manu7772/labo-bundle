<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Form\base\BaseAppType;
use Aequation\LaboBundle\Model\Interface\ImageOwnerInterface;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ImageType extends BaseAppType
{
    public const CLASSNAME = Image::class;
    public const DELETE_IMAGE = true;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'nom',
            ])
            ->add('file', VichImageType::class, [
                'label' => false,
                'allow_delete' => false,
            ]
        );
        parent::buildForm($builder, $options);
    }

}