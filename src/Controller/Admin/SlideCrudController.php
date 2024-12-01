<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Security\Voter\SlideVoter;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Field\ThumbnailField;
use Aequation\LaboBundle\Form\Type\OverlayType;
use Aequation\LaboBundle\Service\Interface\SlideServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Service\Tools\Strings;

use App\Entity\Slide;
use App\Form\Type\SlidebaseType;

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
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Form\Type\VichImageType;

#[IsGranted('ROLE_COLLABORATOR')]
class SlideCrudController extends BaseCrudController
{

    public const ENTITY = Slide::class;
    public const VOTER = SlideVoter::class;

    public function configureFilters(Filters $filters): Filters
    {
        /** @var Slide */
        $model = new Slide;
        return $filters
            ->add(TextFilter::new('name', 'Nom'))
            ->add(TextFilter::new('filename', 'Nom du fichier'))
            ->add(ChoiceFilter::new('slidetype', 'Type de diaporama')->setChoices($model->getSlidetypeChoices(false)))
            // ->add(NumericFilter::new('totalprice'))
            ->add(NumericFilter::new('size', 'Poids'))
            ->add(DateTimeFilter::new('createdAt', 'Création'))
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
                yield IdField::new('id');
                yield AssociationField::new('owner', 'Propriétaire');
                yield TextField::new('name');
                yield TextField::new('slug');
                yield TextField::new('slidetypeAsText', 'Type de diapositive');
                yield TextField::new('filename');
                yield TextField::new('mime');
                yield TextField::new('originalname');
                // yield TextField::new('content')->renderAsHtml();
                yield TextField::new('dimensions');
                yield ThumbnailField::new('_self', 'Image')
                    ->setBasePath($this->getParameter('vich_dirs.slider_slides'));
                yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$info['entity']->getMaxSlidebases().')')
                    ->setEntryType(SlidebaseType::class);
                yield IntegerField::new('size')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield BooleanField::new('enabled', 'Activée');
                yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                yield TextEditorField::new('overlays', 'Textes')->formatValue(function ($value) { return Encoders::getPrintr($value, 5, true); });
                break;
                
            case 'slide_collection_in_slider':
                // $slide = $this->createEntity(static::ENTITY, false);
                // $info['entity']->addSlide($slide);
                // dump($info['entity'], $slide);
                yield FormField::addColumn('col-md-6');
                    yield TextField::new('name')->setRequired(true);
                    yield ChoiceField::new('classes', 'Styles')
                        ->setChoices(function (?Slide $slide) { return $slide ? $slide->getClassesChoices() : Slide::getClassesChoices(); })
                        ->setRequired(false)
                        ->allowMultipleChoices(true);
                    yield CollectionField::new('overlays')
                        ->setRequired(false)
                        ->allowAdd()
                        ->allowDelete()
                        ->setEntryType(OverlayType::class)
                        ->setFormTypeOption('by_reference', false);
                    // yield BooleanField::new('enabled', 'Activé')->setColumns(3)->setHelp('Si cette diapositive n\'est pas activée, ell ne sera pas visible dans le diaporama qui la contient.');
                    // yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN')->setColumns(3);
                yield FormField::addColumn('col-md-6');
                    yield TextField::new('file', 'Image')
                        ->setRequired(true)
                        ->setFormType(VichImageType::class);
                    yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$info['entity']->getMaxSlidebases().')')
                        ->allowAdd(true)
                        ->allowDelete()
                        ->setEntryType(SlidebaseType::class)
                        ->setEntryIsComplex()
                        ->setHelp('Placer ici d\'autres images si nécessaire');
                    // yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            case Crud::PAGE_NEW:
                $allowAdd = $info['entity']->canAddSlidebases();
                $hasSbases = $info['entity']->hasSlidebasesOption();
                $hasOverlays = $info['entity']->hasOverlaysOption();

                yield FormField::addTab('Informations')
                    ->setIcon('fa6-solid:info');

                yield TextField::new('name')->setColumns(12)->setRequired(true);
                yield ChoiceField::new('classes', 'Styles')
                    ->setChoices(function (?Slide $slide) { return $slide ? $slide->getClassesChoices() : Slide::getClassesChoices(); })
                    ->setRequired(false)
                    ->allowMultipleChoices(true)
                    ->setColumns(12);
                yield TextField::new('title', 'Titre de la slide')->setColumns(6)->setRequired(false);
                yield ChoiceField::new('slidetype', 'Type de diaporama')
                    ->setChoices($info['entity']->getSlidetypeChoices(true))
                    ->escapeHtml(false)
                    ->setColumns(6)
                    ->setRequired(false);
                yield TextEditorField::new('content','Texte')->setColumns(12)->formatValue(fn ($value) => Strings::markup($value));
    
                yield FormField::addTab('Contenu média')
                    ->setIcon('fa6-solid:camera');

                yield TextField::new('file', 'Image')
                    ->setRequired(true)
                    ->setFormType(VichImageType::class)
                    ->setColumns(6);
                if($hasOverlays) {
                    yield CollectionField::new('overlays')
                        ->setRequired(false)
                        ->allowAdd()
                        ->allowDelete()
                        ->setEntryType(OverlayType::class)
                        ->setFormTypeOption('by_reference', false)
                        ->setColumns(6);
                }
                if($hasSbases) {
                    yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$info['entity']->getMaxSlidebases().')')
                        ->allowAdd($allowAdd)
                        ->allowDelete()
                        ->setEntryType(SlidebaseType::class)
                        ->setEntryIsComplex()
                        ->setColumns(6)
                        ->setHelp($allowAdd ? 'Placer ici d\'autres images si nécessaire' : 'Vous ne pouvez pas ajouter d\'autres images, le maxium est atteint');
                }

                yield FormField::addTab('Statut')
                    ->setIcon('fa6-solid:lock');

                yield BooleanField::new('enabled', 'Activé')->setColumns(6)->setHelp('Si cette diapositive n\'est pas activée, ell ne sera pas visible dans le diaporama qui la contient.');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN')->setColumns(6);
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            case Crud::PAGE_EDIT:
                $allowAdd = $info['entity']->canAddSlidebases();
                $hasSbases = $info['entity']->hasSlidebasesOption();
                $hasOverlays = $info['entity']->hasOverlaysOption();

                yield FormField::addTab('Informations')
                    ->setIcon('fa6-solid:info');

                yield TextField::new('name')->setColumns(12)->setRequired(true);
                yield ChoiceField::new('classes', 'Styles')
                    ->setChoices(function (?Slide $slide): array { return $slide ? $slide->getClassesChoices() : Slide::getClassesChoices(); })
                    ->setRequired(false)
                    ->allowMultipleChoices(true)
                    ->setColumns(12);
                yield TextField::new('title', 'Titre de la slide')->setColumns(6)->setRequired(false);
                yield ChoiceField::new('slidetype', 'Type de diaporama')
                    ->setChoices($info['entity']->getSlidetypeChoices(true))
                    ->escapeHtml(false)
                    ->setColumns(6)
                    ->setRequired(false);
                yield TextEditorField::new('content','Texte')->setColumns(12)->formatValue(fn ($value) => Strings::markup($value));

                yield FormField::addTab('Contenu média')
                    ->setIcon('fa6-solid:camera');

                yield TextField::new('file', 'Image')
                    ->setFormType(VichImageType::class)
                    ->setFormTypeOption('allow_delete', false)
                    ->setColumns(6);
                if($hasOverlays) {
                    yield CollectionField::new('overlays')
                        ->setRequired(false)
                        ->allowAdd()
                        ->allowDelete()
                        ->setEntryType(OverlayType::class)
                        ->setFormTypeOption('by_reference', false)
                        ->setColumns(6);
                }
                if($hasSbases) {
                    yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$info['entity']->getMaxSlidebases().')')
                        ->allowAdd($allowAdd)
                        ->allowDelete()
                        ->setEntryType(SlidebaseType::class)
                        ->setEntryIsComplex()
                        ->setColumns(6)
                        ->setHelp($allowAdd ? 'Placer ici d\'autres images si nécessaire' : 'Vous ne pouvez pas ajouter d\'autres images, le maxium est atteint');
                }

                yield FormField::addTab('Statut')
                    ->setIcon('fa6-solid:lock');

                yield BooleanField::new('enabled', 'Activé')->setColumns(6)->setHelp('Si cette diapositive n\'est pas activée, ell ne sera pas visible dans le diaporama qui la contient.');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN')->setColumns(6);
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name');
                // yield TextField::new('filename');
                yield TextField::new('slidetype', 'Type diapo');
                yield ThumbnailField::new('_self', 'Image')
                    ->setBasePath($this->getParameter('vich_dirs.slider_slides'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                // yield TextEditorField::new('content')->formatValue(fn ($value) => Strings::markup($value));
                yield IntegerField::new('size')->setTextAlign('center')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield BooleanField::new('enabled', 'Activé')->setTextAlign('center');
                yield AssociationField::new('owner', 'Propriétaire');
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm');
                break;
        }
    }

}