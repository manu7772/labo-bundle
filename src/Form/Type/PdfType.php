<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Form\base\BaseAppType;
use Aequation\LaboBundle\Model\Interface\PdfInterface;
use Aequation\LaboBundle\Service\Interface\PdfServiceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PdfType extends BaseAppType
{
    public const CLASSNAME = Pdf::class;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('file', VichFileType::class, [
                'label' => 'Fichier PDF',
                'required' => true,
            ])
            ->add('photo', PhotoType::class, [
                'label' => 'Vignette',
                'required' => false,
            ])
        ;

    }

}