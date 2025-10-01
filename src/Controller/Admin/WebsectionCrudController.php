<?php
namespace Aequation\LaboBundle\Controller\Admin;

use ReflectionClass;
use App\Entity\Urlink;
use App\Entity\Videolink;
use App\Entity\Websection;
use Doctrine\ORM\QueryBuilder;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\FormInterface;
use Aequation\LaboBundle\Form\Type\PdfType;
use Aequation\LaboBundle\Field\CKEditorField;
use Aequation\LaboBundle\Form\Type\PhotoType;
use Aequation\LaboBundle\Field\ThumbnailField;
use App\Controller\Admin\UrlinkCrudController;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Aequation\LaboBundle\Service\Tools\Strings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

use Vich\UploaderBundle\Form\Type\VichImageType;
use App\Controller\Admin\VideolinkCrudController;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Aequation\LaboBundle\Security\Voter\WebsectionVoter;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\WebsectionServiceInterface;

#[IsGranted('ROLE_COLLABORATOR')]
class WebsectionCrudController extends BaseCrudController
{
    public const ENTITY = Websection::class;
    public const VOTER = WebsectionVoter::class;

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkGrants($pageName);
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield FormField::addTab(label: 'Websection', icon: $this->getLaboContext()->getInstance()::ICON);

                yield FormField::addColumn('col-md-12 col-lg-6');
                    yield FormField::addPanel(label: 'Websection', icon: Websection::ICON);
                        yield IdField::new('id');
                        yield AssociationField::new('owner', 'Propriétaire')->setCrudController(UserCrudController::class);
                        yield TextField::new('name', 'Nom');
                        yield IntegerField::new('orderitem', 'Priorité')->setHelp('Ordre d\'affichage de la page dans les listes.');
                        yield TextField::new('sectiontype', 'Type de section');
                        yield TextField::new('title', 'Titre de la section');
                        yield AssociationField::new('mainmenu', 'Menu intégré')->setCrudController(MenuCrudController::class);
                        yield TextField::new('twigfileName', 'Nom du modèle');
                        yield TextField::new('content', 'Texte de la section')->renderAsHtml();
                
                yield FormField::addColumn('col-md-12 col-lg-6');
                    yield FormField::addPanel(label: 'Médias associés', icon: 'fa6-solid:link');
                        yield CollectionField::new('categorys');
                        yield ArrayField::new('pdfiles', 'Fichiers PDF');
                        yield ArrayField::new('relinks', 'Urls');
                        yield ArrayField::new('videolinks', 'Vidéos');
                        // SLIDER
                        $slider = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('slider', $pageName);
                        switch (true) {
                            case $slider instanceof FieldInterface:
                                /** @var FieldTrait $slider */
                                yield $slider;
                                break;
                            case $slider === true:
                                yield AssociationField::new('slider', 'Diaporama');
                                break;
                        }
                        // PHOTO
                        $photo = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('photo', $pageName);
                        switch (true) {
                            case $photo instanceof FieldInterface:
                                /** @var FieldTrait $photo */
                                yield $photo;
                                break;
                            case $photo === true:
                                yield ThumbnailField::new('photo', 'Photo')
                                    ->setBasePath($this->getParameter('vich_dirs.item_photo'));
                                break;
                        }

                    yield FormField::addPanel(label: 'Autres', icon: 'fa6-solid:info');
                        yield BooleanField::new('prefered', 'Section par défaut');
                        yield BooleanField::new('enabled', 'Activée');
                        yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());
                        yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());

                yield FormField::addTab(label: 'Super admin', icon: 'tabler:lock-filled')->setPermission('ROLE_SUPER_ADMIN');
                    yield TextField::new('euid', 'Euid')->setPermission('ROLE_SUPER_ADMIN')->setHelp('Identifiant unique de la section');
                    yield TextField::new('twigfile', 'Chemin du modèle')->setPermission('ROLE_SUPER_ADMIN')->setHelp('Chemin relatif vers le fichier Twig');
                    yield ArrayField::new('parents', 'Parents')->setPermission('ROLE_SUPER_ADMIN')->setHelp('Liste des pages web utilisant cette section');
                    yield TextareaField::new('content', 'Texte compilé')
                        ->formatValue(fn ($value) => $this->getLaboContext()->getInstance()->dumpContent())
                        ->setColumns(12)->setPermission('ROLE_SUPER_ADMIN')
                        ->setHelp('Texte compilé avec les variables twig. Utile pour le débogage.')
                        ;
                    yield TextareaField::new('relationOrderDetails', 'Rel.order info')->setPermission('ROLE_SUPER_ADMIN');
                    yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                break;
            case Crud::PAGE_NEW:
                // yield TextField::new('name', 'Nom de la section')
                //     ->setHelp('Utilisez un nom simple et pas trop long. <strong>Ce nom est uniquement utilisé pour l\'administration et n\'est pas affiché dans le contenu la page web</strong>.')
                //     ->setColumns(6);
                // yield ChoiceField::new('twigfile', 'Modèle de mise en page')
                //     ->setChoices(static fn (Websection $websection): array => $websection->getTwigfileChoices() ?: [])
                //     ->escapeHtml(false)
                //     ->setFormTypeOption('by_reference', false)
                //     ->setHelp('Choisissez un modèle de mise en page pour cette section. Cela définira le rendu dans la page web, et également le type de section (banner, section classique, footer, etc.).')
                //     ->setColumns(6);
                // // yield ChoiceField::new('sectiontype', 'Type de section')
                // //     ->setChoices(static fn (Websection $websection): array => $websection->getTwigfileMetadata()->getSectiontypeChoices() ?: [])
                // //     ->setColumns(4);
                // // TITLE
                // $title = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('title', $pageName);
                // switch (true) {
                //     case $title instanceof FieldInterface:
                //         /** @var FieldTrait $title */
                //         yield $title->setColumns(5);
                //         break;
                //     case $title === true:
                //         yield TextField::new('title', 'Titre de la section')
                //             ->setColumns(8)
                //             ->setRequired(false);
                //         break;
                // }
                // yield AssociationField::new('categorys','Catégories')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Websection::class))
                //     // ->autocomplete()
                //     ->setSortProperty('name')
                //     ->setFormTypeOptions(['by_reference' => false])
                //     ->setColumns(4);
                // yield BooleanField::new('prefered', 'Section par défaut')
                //     ->setHelp('Cette section sera affectée automatiquement lors de la création d\'une nouvelle page web')
                //     ->setColumns(3);
                // // cumputed form fields
                // // CONTENT
                // $content = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('content', $pageName);
                // switch (true) {
                //     case $content instanceof FieldInterface:
                //         /** @var FieldTrait $content */
                //         yield $content->setColumns(12);
                //         break;
                //     case $content === true:
                //         yield CKEditorField::new('content', 'Texte de la section')
                //             ->formatValue(fn ($value) => Strings::markup($value))
                //             ->setColumns(12);
                //         break;
                // }
                // // MAINMENU
                // $mainmenu = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('mainmenu', $pageName);
                // switch (true) {
                //     case $mainmenu instanceof FieldInterface:
                //         /** @var FieldTrait $mainmenu */
                //         yield $mainmenu->setColumns(4);
                //         break;
                //     case $mainmenu === true:
                //         yield AssociationField::new('mainmenu', 'Menu intégré')
                //             ->setColumns(4);
                //         break;
                // }
                // // PHOTO
                // $photo = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('photo', $pageName);
                // switch (true) {
                //     case $photo instanceof FieldInterface:
                //         /** @var FieldTrait $photo */
                //         yield $photo->setColumns(6);
                //         break;
                //     case $photo === true:
                //         yield TextField::new('photo', 'Photo')
                //             ->setFormType(PhotoType::class)
                //             ->setColumns(6);
                //         break;
                // }
                // // SLIDER
                // $slider = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('slider', $pageName);
                // switch (true) {
                //     case $slider instanceof FieldInterface:
                //         /** @var FieldTrait $slider */
                //         yield $slider->setColumns(6);
                //         break;
                //     case $slider === true:
                //         yield AssociationField::new('slider', 'Diaporama')
                //             ->setHelp('Vous pouvez utiliser un diaporama existant, ou en créer un nouveau pour cette section')
                //             ->setColumns(6);
                //         break;
                // }
                // yield CollectionField::new('pdfiles', 'Fichiers PDF')
                //     ->setEntryType(PdfType::class);
                // yield BooleanField::new('enabled', 'Activée');
                // yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                // yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                // break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name', 'Nom de la section')
                    ->setHelp('Utilisez un nom simple et pas trop long. <strong>Ce nom est uniquement utilisé pour l\'administration et n\'est pas affiché dans le contenu la page web</strong>.')
                    ->setColumns(6);
                $twigfile = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('twigfile', $pageName);
                switch (true) {
                    case $twigfile instanceof FieldInterface:
                        /** @var FieldTrait $twigfile */
                        yield $twigfile->setColumns(6);
                        break;
                    case $twigfile === true:
                        yield ChoiceField::new('twigfile', 'Modèle de mise en page')
                            ->setChoices(static fn (Websection $websection): array => $websection->getTwigfileChoices() ?: [])
                            ->escapeHtml(false)
                            ->setFormTypeOption('by_reference', false)
                            ->setColumns(6);
                        break;
                }
                yield BooleanField::new('prefered', 'Section par défaut')->setColumns(2)->setHelp('Définir comme section attribuée par défaut dans une nouvelle page web');
                yield ChoiceField::new('sectiontype', 'Type de section')
                    ->setChoices(static fn (Websection $websection): array => $websection->getTwigfileMetadata()->getSectiontypeChoices() ?: [])->setDisabled(!$this->isGranted('ROLE_SUPER_ADMIN'))
                    ->setFormTypeOption('by_reference', false)
                    ->setColumns(4);
                // cumputed form fields
                // TITLE
                $title = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('title', $pageName);
                switch (true) {
                    case $title instanceof FieldInterface:
                        /** @var FieldTrait $title */
                        yield $title->setColumns(6);
                        break;
                    case $title === true:
                        yield TextField::new('title', 'Titre de la section')
                            ->setColumns(6)
                            ->setRequired(false);
                        break;
                }

                yield FormField::addPanel('Liens externes', Urlink::ICON);
                    yield CollectionField::new('relinks', 'Urls')
                        ->setLabel(false)
                        ->useEntryCrudForm(UrlinkCrudController::class, 'new_embeded', 'edit_embeded')
                        ->setEntryToStringMethod(fn (?LaboRelinkInterface $entity) => $entity ? (empty($entity->getName()) ? $entity->getLinktitle() : $entity->getName()) : '---')
                        ->setColumns(12)
                        ->setEntryIsComplex(true)
                        ->setFormTypeOption('by_reference', false)
                        ->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(UrlinkCrudController::ENTITY))
                        ;
                    // yield FormField::addTab(label: false, icon: Videolink::ICON);
                    yield FormField::addPanel('Vidéos', Videolink::ICON);
                    yield CollectionField::new('videolinks', 'Vidéos')
                        ->setLabel(false)
                        ->useEntryCrudForm(VideolinkCrudController::class, 'new_embeded', 'edit_embeded')
                        ->setEntryToStringMethod(fn (?LaboRelinkInterface $entity) => $entity ? (empty($entity->getName()) ? $entity->getLinktitle() : $entity->getName()) : '---')
                        ->setColumns(12)
                        ->setEntryIsComplex(true)
                        ->setFormTypeOption('by_reference', false)
                        // ->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(VideolinkCrudController::ENTITY))
                        ;
                    yield CollectionField::new('test','Tests')
                        ->allowAdd(true)
                        ->allowDelete(true)
                        ->showEntryLabel(false)
                        ->setFormTypeOptions(
                            [
                                'mapped' => false,
                                'entry_type' => ChoiceType::class,
                                'entry_options' => [
                                    'required' => true,
                                    'choices' => [
                                        'Nashville' => 'nashville',
                                        'Paris'     => 'paris',
                                        'Berlin'    => 'berlin',
                                        'London'    => 'london',
                                    ],
                                ],
                                'allow_add' => true,
                                'allow_delete' => true,
                            ]
                        )
                        ->setColumns(12)
                        ;
                    yield CollectionField::new('texts','Textes de la section ['.$this->getLaboContext()->getInstance()->getSectiontype().']')
                        ->setEntryType(CKEditorType::class)
                        ->allowAdd(true)
                        ->allowDelete(true)
                        ->showEntryLabel(false)
                        ->setFormTypeOptions([
                            'mapped' => false,
                            'by_reference' => false,
                            // 'entry_options' => [
                            //     'attr' => ['style' => 'width: 100%; min-height: 200px;'],
                            // ]
                        ])
                        // ->renderExpanded(true)
                        ->setColumns(12)
                        ;
                // yield AssociationField::new('categorys')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Websection::class))
                //     // ->autocomplete()
                //     ->setSortProperty('name')
                //     ->setFormTypeOptions(['by_reference' => false])
                //     ->setColumns(4);
                // PHOTO
                $photo = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('photo', $pageName);
                switch (true) {
                    case $photo instanceof FieldInterface:
                        /** @var FieldTrait $photo */
                        yield $photo->setColumns(4);
                        break;
                    case $photo === true:
                        yield TextField::new('photo', 'Photo')
                            ->setFormType(PhotoType::class)
                            // ->setFormTypeOptions(['allow_delete' => false])
                            ->setColumns(4);
                        break;
                }
                // CONTENT
                $content = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('content', $pageName);
                switch (true) {
                    case $content instanceof FieldInterface:
                        /** @var FieldTrait $content */
                        yield $content->setColumns(12);
                        break;
                    case $content === true:
                        yield CKEditorField::new('content', 'Texte de la section')
                            ->formatValue(fn ($value) => Strings::markup($value))
                            ->setColumns(12);
                        break;
                }
                // MAINMENU
                $mainmenu = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('mainmenu', $pageName);
                switch (true) {
                    case $mainmenu instanceof FieldInterface:
                        /** @var FieldTrait $mainmenu */
                        yield $mainmenu->setColumns(6);
                        break;
                    case $mainmenu === true:
                        yield AssociationField::new('mainmenu', 'Menu intégré')
                            ->setColumns(6);
                        break;
                }
                // SLIDER
                $slider = $this->getLaboContext()->getInstance()->getTwigfileMetadata()->getEasyadminField('slider', $pageName);
                switch (true) {
                    case $slider instanceof FieldInterface:
                        /** @var FieldTrait $slider */
                        yield $slider->setColumns(6);
                        break;
                    case $slider === true:
                        yield AssociationField::new('slider', 'Diaporama')
                            ->setHelp('Vous pouvez utiliser un diaporama existant, ou en créer un nouveau pour cette section')
                            ->setColumns(6);
                        break;
                }
                yield CollectionField::new('pdfiles', 'Fichiers PDF')
                    ->setEntryType(PdfType::class);
                yield BooleanField::new('enabled', 'Activée');
                yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield IntegerField::new('orderitem', 'Priorité')->setHelp('Ordre d\'affichage de la page dans les listes.')->setColumns(3);
                break;
            default:
                // yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield ThumbnailField::new('photo', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.item_photo'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield TextField::new('name', 'Nom');
                yield TextField::new('sectiontype', 'Type de section')->setTextAlign('center');
                // yield TextField::new('title', 'Titre');
                yield TextField::new('twigfileName', 'Modèle')->setTextAlign('center');
                // yield TextEditorField::new('content', 'Texte de la section')
                //     ->formatValue(fn ($value) => Strings::markup($value))
                //     ->setTextAlign('center')
                //     ->setSortable(false);
                // yield AssociationField::new('mainmenu', 'Menu intégré')->setTextAlign('center');
                // yield AssociationField::new('owner', 'Propriétaire')->setTextAlign('center');
                // yield IntegerField::new('orderitem', 'Ord.');
                yield BooleanField::new('prefered', 'Section par défaut')->setTextAlign('center');
                // yield AssociationField::new('pdfiles', 'PDF')->setTextAlign('center')->setSortable(false);
                yield BooleanField::new('enabled', 'Activée')->setTextAlign('center');
                // yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());
                break;
        }
    }

    public function createEntity(
        string $entityFqcn,
        bool $checkGrant = true,
    ): ?AppEntityInterface
    {
        // if($checkGrant) $this->checkGrants(Crud::PAGE_NEW);
        /** @var AppEntityInterface */
        $entity = $this->manager->getNew();
        if($this->getQueryValue('type')) {
            $entity->setSectiontype($this->getQueryValue('type'));
        }
        return $entity;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $type = $this->getQueryValue('type');
        if($type) {
            $queryBuilder->andWhere('entity.sectiontype = :sectiontype')
                ->setParameter('sectiontype', $type);
        }
        return $queryBuilder;
    }

}