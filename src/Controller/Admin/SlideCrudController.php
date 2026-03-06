<?php
namespace Aequation\LaboBundle\Controller\Admin;

// App
use App\Entity\Slide;
// Aequation
use Aequation\LaboBundle\Entity\LaboSlide;
use Aequation\LaboBundle\Field\CKEditorField;
use Aequation\LaboBundle\Field\ThumbnailField;
use Aequation\LaboBundle\Form\Type\OverlayType;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Form\Type\SlidebaseType;
use Aequation\LaboBundle\Security\Voter\SlideVoter;
use Aequation\LaboBundle\Model\Final\FinalLaboSlideInterface;
use Aequation\LaboBundle\Service\Interface\SlideServiceInterface;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Service\Interface\AppEntityServiceInterface;
// Symfony
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

#[IsGranted('ROLE_COLLABORATOR')]
abstract class SlideCrudController extends BaseCrudController
{

    public const ENTITY = Slide::class;
    public const VOTER = SlideVoter::class;

    /** @var SlideServiceInterface */
    public readonly AppEntityServiceInterface $entityService;

    public function configureFilters(Filters $filters): Filters
    {
        /** @var Slide */
        $model = $this->entityService->getModel();
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
                yield TextField::new('imagefilterName', 'Format de l\'image');
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
                        ->setChoices(function (?FinalLaboSlideInterface $slide) { return $slide ? $slide->getClassesChoices() : LaboSlide::getClassesChoices(); })
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
            case Crud::PAGE_EDIT:
                $allowAdd = $this->getLaboContext()->getInstance()->canAddSlidebases();
                $hasSbases = $this->getLaboContext()->getInstance()->hasSlidebasesOption();
                $hasOverlays = $this->getLaboContext()->getInstance()->hasOverlaysOption();
                $slide = $this->getContext()->getEntity()->getInstance();

                yield FormField::addTab('Informations')
                    ->setIcon('tabler:info-circle');

                    yield FormField::addColumn(6);
                        yield TextField::new('name')->setRequired(true)->setHelp('Nom interne de la diapositive, utilisé pour l\'identifier dans l\'administration. Il n\'est pas affiché sur le site.');
                        yield TextField::new('title', 'Titre de la slide')->setRequired(false)->setHelp('Titre de la slide, affiché sur le site selon les styles appliqués.');
                    yield FormField::addColumn(6);
                        yield ChoiceField::new('slidetype', 'Type de diaporama')
                            ->setChoices($this->getLaboContext()->getInstance()->getSlidetypeChoices(true))
                            ->escapeHtml(false)
                            ->setRequired(false)
                            ->setHelp('Le type de diaporama détermine les dimensions de l\'image et les options disponibles pour la diapositive. Si vous changez le type de diaporama d\'une diapositive déjà créée, vérifiez que les images utilisées correspondent bien aux dimensions requises par le nouveau type.');
                        yield ChoiceField::new('classes', 'Styles')
                            ->setChoices(function (?FinalLaboSlideInterface $slide): array { return $slide ? $slide->getClassesChoices() : LaboSlide::getClassesChoices(); })
                            ->setRequired(false)
                            ->allowMultipleChoices(true)
                            ->setHelp('Styles supplémentaires à appliquer à la diapositive pour modifier son aspect visuel : couleurs sépia, monochrome, couleurs négatives, flous, etc.');
                    yield FormField::addColumn(12);
                        yield CKEditorField::new('content','Texte')->formatValue(fn ($value) => Strings::markup($value));

                yield FormField::addTab('Contenu média')
                    ->setIcon('tabler:camera');

                    yield FormField::addColumn(6);
                        yield TextField::new('file', 'Image')
                            ->setFormType(VichImageType::class)
                            ->setFormTypeOption('allow_delete', false);
                        yield ChoiceField::new('imagefilter', 'Format de l\'image')
                            ->setChoices($this->entityService->getLiipFilterChoices(0, 0, $slide))
                            ->setRequired(true);

                    yield FormField::addColumn(6);
                        if($hasOverlays) {
                            yield CollectionField::new('overlays', 'Textes')
                                ->setRequired(false)
                                ->allowAdd()
                                ->allowDelete()
                                ->setEntryType(OverlayType::class)
                                ->setFormTypeOption('by_reference', false);
                        }
                        if($hasSbases) {
                            yield CollectionField::new('slidebases', 'Images additionnelles (max. '.$this->getLaboContext()->getInstance()->getMaxSlidebases().')')
                                ->allowAdd($allowAdd)
                                ->allowDelete()
                                ->setEntryType(SlidebaseType::class)
                                ->setEntryIsComplex()
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