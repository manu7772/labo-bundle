<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Security\Voter\SlideVoter;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Entity\LaboUser;
use Aequation\LaboBundle\Field\CKEditorField;
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
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id');
                yield AssociationField::new('owner', 'Propriétaire');
                yield TextField::new('name');
                yield IntegerField::new('orderitem', 'Priorité')->setHelp('Ordre d\'affichage de la page dans les listes.');
                yield TextField::new('slug');
                yield TextField::new('slidetypeAsText', 'Type de diapositive');
                yield TextField::new('filename');
                yield TextField::new('mime');
                yield TextField::new('originalname');
                // yield TextField::new('content')->renderAsHtml();
                yield TextField::new('dimensions');
                yield ThumbnailField::new('_self', 'Image')
                    ->setBasePath($this->getParameter('vich_dirs.slider_slides'));
                yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$this->getLaboContext()->getInstance()->getMaxSlidebases().')')
                    ->setEntryType(SlidebaseType::class);
                yield IntegerField::new('size')->formatValue(fn ($value) => intval($value/1024).'Ko');
                yield BooleanField::new('enabled', 'Activée');
                yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());
                yield TextEditorField::new('overlays', 'Textes')->formatValue(fn ($value) => Encoders::getPrintr($value, 5, true));
                break;
                
            case 'slide_collection_in_slider':
                // $slide = $this->createEntity(static::ENTITY, false);
                // $this->getLaboContext()->getInstance()->addSlide($slide);
                yield FormField::addColumn('col-md-6');
                    yield TextField::new('name')->setRequired(true);
                    yield ChoiceField::new('classes', 'Styles')
                        ->setChoices(function (?Slide $slide) { return $slide ? $slide->getClassesChoices() : Slide::getClassesChoices(); })
                        ->setRequired(false)
                        ->allowMultipleChoices(true);
                    yield CollectionField::new('overlays', 'Textes')
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
                    yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$this->getLaboContext()->getInstance()->getMaxSlidebases().')')
                        ->allowAdd(true)
                        ->allowDelete()
                        ->setEntryType(SlidebaseType::class)
                        ->setEntryIsComplex()
                        ->setHelp('Placer ici d\'autres images si nécessaire');
                    // yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            case Crud::PAGE_NEW:
                $allowAdd = $this->getLaboContext()->getInstance()->canAddSlidebases();
                $hasSbases = $this->getLaboContext()->getInstance()->hasSlidebasesOption();
                $hasOverlays = $this->getLaboContext()->getInstance()->hasOverlaysOption();

                yield FormField::addTab('Informations')
                    ->setIcon('tabler:info-circle');

                yield TextField::new('name')->setColumns(12)->setRequired(true);
                yield ChoiceField::new('classes', 'Styles')
                    ->setChoices(function (?Slide $slide) { return $slide ? $slide->getClassesChoices() : Slide::getClassesChoices(); })
                    ->setRequired(false)
                    ->allowMultipleChoices(true)
                    ->setColumns(12);
                yield TextField::new('title', 'Titre de la slide')->setColumns(6)->setRequired(false);
                yield ChoiceField::new('slidetype', 'Type de diaporama')
                    ->setChoices($this->getLaboContext()->getInstance()->getSlidetypeChoices(true))
                    ->escapeHtml(false)
                    ->setColumns(6)
                    ->setRequired(false);
                yield CKEditorField::new('content','Texte')->setColumns(12)->formatValue(fn ($value) => Strings::markup($value));
    
                yield FormField::addTab('Contenu média')
                    ->setIcon('tabler:camera');

                yield TextField::new('file', 'Image')
                    ->setRequired(true)
                    ->setFormType(VichImageType::class)
                    ->setColumns(6);
                if($hasOverlays) {
                    yield CollectionField::new('overlays', 'Textes')
                        ->setRequired(false)
                        ->allowAdd()
                        ->allowDelete()
                        ->setEntryType(OverlayType::class)
                        ->setFormTypeOption('by_reference', false)
                        ->setColumns(6);
                }
                if($hasSbases) {
                    yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$this->getLaboContext()->getInstance()->getMaxSlidebases().')')
                        ->allowAdd($allowAdd)
                        ->allowDelete()
                        ->setEntryType(SlidebaseType::class)
                        ->setEntryIsComplex()
                        ->setColumns(6)
                        ->setHelp($allowAdd ? 'Placer ici d\'autres images si nécessaire' : 'Vous ne pouvez pas ajouter d\'autres images, le maxium est atteint');
                }

                yield FormField::addTab('Statut')
                    ->setIcon('tabler:lock');

                yield BooleanField::new('enabled', 'Activé')->setColumns(6)->setHelp('Si cette diapositive n\'est pas activée, ell ne sera pas visible dans le diaporama qui la contient.');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN')->setColumns(6);
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            case Crud::PAGE_EDIT:
                $allowAdd = $this->getLaboContext()->getInstance()->canAddSlidebases();
                $hasSbases = $this->getLaboContext()->getInstance()->hasSlidebasesOption();
                $hasOverlays = $this->getLaboContext()->getInstance()->hasOverlaysOption();

                yield FormField::addTab('Informations')
                    ->setIcon('tabler:info-circle');

                yield TextField::new('name')->setColumns(12)->setRequired(true);
                yield ChoiceField::new('classes', 'Styles')
                    ->setChoices(function (?Slide $slide): array { return $slide ? $slide->getClassesChoices() : Slide::getClassesChoices(); })
                    ->setRequired(false)
                    ->allowMultipleChoices(true)
                    ->setColumns(12);
                yield TextField::new('title', 'Titre de la slide')->setColumns(6)->setRequired(false);
                yield ChoiceField::new('slidetype', 'Type de diaporama')
                    ->setChoices($this->getLaboContext()->getInstance()->getSlidetypeChoices(true))
                    ->escapeHtml(false)
                    ->setColumns(6)
                    ->setRequired(false);
                yield CKEditorField::new('content','Texte')->setColumns(12)->formatValue(fn ($value) => Strings::markup($value));

                yield FormField::addTab('Contenu média')
                    ->setIcon('tabler:camera');

                yield TextField::new('file', 'Image')
                    ->setFormType(VichImageType::class)
                    ->setFormTypeOption('allow_delete', false)
                    ->setColumns(6);
                if($hasOverlays) {
                    yield CollectionField::new('overlays', 'Textes')
                        ->setRequired(false)
                        ->allowAdd()
                        ->allowDelete()
                        ->setEntryType(OverlayType::class)
                        ->setFormTypeOption('by_reference', false)
                        ->setColumns(6);
                }
                if($hasSbases) {
                    yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$this->getLaboContext()->getInstance()->getMaxSlidebases().')')
                        ->allowAdd($allowAdd)
                        ->allowDelete()
                        ->setEntryType(SlidebaseType::class)
                        ->setEntryIsComplex()
                        ->setColumns(6)
                        ->setHelp($allowAdd ? 'Placer ici d\'autres images si nécessaire' : 'Vous ne pouvez pas ajouter d\'autres images, le maxium est atteint');
                }

                yield FormField::addTab('Statut')
                    ->setIcon('tabler:lock');

                yield BooleanField::new('enabled', 'Activé')->setColumns(6)->setHelp('Si cette diapositive n\'est pas activée, ell ne sera pas visible dans le diaporama qui la contient.');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN')->setColumns(6);
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield IntegerField::new('orderitem', 'Priorité')->setHelp('Ordre d\'affichage de la page dans les listes.')->setColumns(3);
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
                yield IntegerField::new('orderitem', 'Ord.');
                yield BooleanField::new('enabled', 'Activé')->setTextAlign('center');
                yield AssociationField::new('owner', 'Propriétaire');
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm');
                break;
        }
    }

}