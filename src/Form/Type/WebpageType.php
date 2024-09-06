<?php
namespace Aequation\LaboBundle\Form\Type;

use Aequation\LaboBundle\Entity\Ecollection;
use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Entity\Photo;
use Aequation\LaboBundle\Entity\Uname;
use Aequation\LaboBundle\Form\Type\PhotoType;
use App\Entity\Category;
use App\Entity\Menu;
use App\Entity\Slider;
use App\Entity\Webpage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class WebpageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('name')
            // ->add('timezone')
            ->add('enabled')
            ->add('twigfile')
            ->add('content')
            ->add('prefered')
            ->add('title')
            ->add('linktitle')
            ->add('items', EntityType::class, [
                'class' => Item::class,
                'choice_label' => 'name',
                'multiple' => true,
            ])
            ->add('categorys', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
            ])
            ->add('mainmenu', EntityType::class, [
                'class' => Menu::class,
                'choice_label' => 'name',
            ])
            ->add('slider', EntityType::class, [
                'class' => Slider::class,
                'choice_label' => 'name',
            ])
            ->add('photo', PhotoType::class, [
                // 'class' => Photo::class,
                // 'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Webpage::class,
        ]);
    }
}
