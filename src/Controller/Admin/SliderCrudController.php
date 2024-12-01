<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Security\Voter\SliderVoter;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Repository\EcollectionRepository;
use Aequation\LaboBundle\Service\Interface\SliderServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Tools\Strings;
use App\Entity\Slide;
use App\Entity\Slider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLABORATOR')]
class SliderCrudController extends BaseCrudController
{
    public const ENTITY = Slider::class;
    public const VOTER = SliderVoter::class;

    public function configureFilters(Filters $filters): Filters
    {
        /** @var Slider */
        $model = new Slider;
        return $filters
            ->add(TextFilter::new('name', 'Nom'))
            ->add(BooleanFilter::new('enabled', 'Activé'))
            ->add(ChoiceFilter::new('slidertype', 'Type de diaporama')->setChoices($model->getSlidertypeChoices(false)))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkGrants($pageName);
        $info = $this->getContextInfo();
        /** @var User $user */
        $user = $this->getUser();
        $timezone = $this->getParameter('timezone');
        $current_tz = $timezone !== $user->getTimezone() ? $user->getTimezone() : $timezone;
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield AssociationField::new('owner', 'Propriétaire');
                yield TextField::new('name', 'Nom');
                yield TextField::new('slug', 'Nom d\'Url');
                yield TextField::new('slidertypeAsText', 'Type de diaporama');
                yield TextField::new('title', 'Titre');
                yield ArrayField::new('items', 'Diapositives');
                yield TextEditorField::new('content', 'Texte')->formatValue(function ($value) { return Strings::markup($value); });
                yield BooleanField::new('enabled', 'Activé');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                break;
            case Crud::PAGE_NEW:
                yield FormField::addTab('Informations')
                    ->setIcon('fa6-solid:info');

                    yield TextField::new('name', 'Nom du diaporama')->setColumns(6)->setHelp('Ce nom est administratif, il n\'est pas utilisé dans le site');
                    yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->setColumns(5)->setHelp('Utilisez un nom simple et pas trop long. <strong>Il est préférable de bien choisir et de ne jamais changer ce slug ensuite</strong> car il changera l\'URL de la page et cela réduit l\'efficacité du référencement du site.<br>Laissez ce champ comme tel pour que le slug soit généré automatiquement (d\'après le texte du champ "Nom du ...")');
                    // yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setColumns(2)->setHelp('Il est recommandé d\'éviter de changer le slug car il est indexé par les moteurs de recherche. Faites-le uniquement si le nom du slug n\'a plus aucun rapport avec le contenu de ce que vous êtes en train d\'éditer.');
                    yield TextField::new('title', 'Titre')->setColumns(6)->setHelp('Titre du diaporama, qui peut être affiché conjointement')->setRequired(false);
                    yield ChoiceField::new('slidertype', 'Type de diaporama')
                        ->setChoices(Slider::getSlidertypeChoices(true))
                        ->escapeHtml(false)
                        ->setColumns(6)
                        ->setRequired(true)
                        ;
                    yield TextEditorField::new('content', 'Texte de présentation')->setColumns(12);
                    // yield TextareaField::new('content', 'Texte')
                    //     ->setFormType(CKEditorType::class)
                    //     ->setFormTypeOptions(
                    //         [
                    //             'config_name' => 'my_config',
                    //             'attr' => ['rows' => '20', 'class' => 'w-100'] ,
                    //         ])
                    //     ->addCssClass('field-ck-editor')
                    //     ->setColumns(12);

                    yield FormField::addTab('Diapositives')
                        ->setIcon('fa6-solid:camera');

                    yield AssociationField::new('items', 'Diapositives')
                        ->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => EcollectionRepository::QB_collectionChoices($qb, Slider::class, 'items'))
                        // ->autocomplete()
                        ->setSortProperty('name')
                        ->setRequired(false)
                        ->setFormTypeOption('by_reference', false)
                        ->setColumns(12);
                    // /** @var Slide */
                    // $new_slide = $this->manager->getNew(Slide::class);
                    // $new_slide->setName('Nouvelle diapositive...');
                    // yield CollectionField::new('items', false)
                    //     // ->showEntryLabel(false)
                    //     ->allowAdd(true)
                    //     ->allowDelete(true)
                    //     ->setRequired(true)
                    //     ->setEntryIsComplex()
                    //     ->useEntryCrudForm(SlideCrudController::class, 'slide_collection_in_slider')
                    //     ->setColumns(12)
                    //     ->setFormTypeOptions([
                    //         'by_reference' => false,
                    //         'prototype_data' => $new_slide,
                    //         // 'empty_data' => new ArrayCollection([$new_slide]),
                    //         // 'data_class' => Slide::class,
                    //         // 'mapped' => false,
                    //         // 'prototype_options' => ['help' => 'Commencez à ajouter une diapositive...'],
                    //         // 'entry_options' => ['help' => 'Ajoutez ou modifiez les diapositives...'],
                    //     ]);

                yield FormField::addTab('Statut')
                    ->setIcon('fa6-solid:lock');

                    yield BooleanField::new('enabled', 'Activé')->setColumns(3)->setHelp('Si le diaporama n\'est pas activé, il ne sera pas visible sur le site.');
                    yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN')->setColumns(3);
                    yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);

                    break;
            case Crud::PAGE_EDIT:
                    yield FormField::addTab('Informations')
                        ->setIcon('fa6-solid:info');
                    yield TextField::new('name', 'Nom du diaporama')->setColumns(6)->setHelp('Ce nom est administratif, il n\'est pas utilisé dans le site');
                    yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->setColumns(3)->setHelp('Utilisez un nom simple et pas trop long. <strong>Il est préférable de ne jamais changer ce slug</strong> car il changera l\'URL de la page et cela réduit l\'efficacité du référencement du site.');
                    yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setColumns(2)->setHelp('Il est recommandé d\'éviter de changer le slug car il est indexé par les moteurs de recherche. Faites-le uniquement si le nom du slug n\'a plus aucun rapport avec le contenu de ce que vous êtes en train d\'éditer.');
                    yield TextField::new('title', 'Titre')->setColumns(6)->setRequired(false);
                    yield ChoiceField::new('slidertype', 'Type de diaporama')
                        ->setChoices(Slider::getSlidertypeChoices(true))
                        ->escapeHtml(false)
                        ->setColumns(6)
                        ->setRequired(true)
                        ;
                    yield TextEditorField::new('content', 'Texte de présentation')->setColumns(12);
                    yield FormField::addTab('Diapositives')
                        ->setIcon('fa6-solid:camera');
                    yield AssociationField::new('items', 'Diapositives')
                        ->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => EcollectionRepository::QB_collectionChoices($qb, Slider::class, 'items'))
                        // ->autocomplete()
                        ->setSortProperty('name')
                        ->setRequired(false)
                        ->setFormTypeOption('by_reference', false)
                        ->setColumns(12);
                    yield FormField::addTab('Statut')
                        ->setIcon('fa6-solid:lock');
                    yield BooleanField::new('enabled', 'Activé')->setColumns(3)->setHelp('Si le diaporama n\'est pas activé, il ne sera pas visible sur le site.');
                    yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN')->setColumns(3);
                    yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);

                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield TextField::new('slidertype', 'Type diapo');
                yield AssociationField::new('items', 'Diapositives')->setTextAlign('center')->setSortable(false);
                yield BooleanField::new('enabled', 'Activé')->setTextAlign('center');
                yield AssociationField::new('owner', 'Propriétaire');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                break;
        }
    }

    // public function createEntity(
    //     string $entityFqcn,
    //     bool $checkGrant = true,
    // ): AppEntityInterface
    // {
    //     if($checkGrant) $this->checkGrants(Crud::PAGE_NEW);
    //     /** @var Slider */
    //     $entity = parent::createEntity($entityFqcn, $checkGrant);
    //     /** @var Slide */
    //     $slide = $this->manager->getNew(Slide::class);
    //     $entity->addSlide($slide);
    //     return $entity;
    // }

}