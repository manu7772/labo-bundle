<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Security\Voter\WebsectionVoter;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Field\ThumbnailField;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Interface\WebsectionServiceInterface;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Form\Type\PhotoType;

use App\Entity\Websection;
use App\Repository\CategoryRepository;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Vich\UploaderBundle\Form\Type\VichImageType;

#[IsGranted('ROLE_COLLABORATOR')]
class WebsectionCrudController extends BaseCrudController
{
    public const ENTITY = Websection::class;
    public const VOTER = WebsectionVoter::class;

    public function __construct(
        WebsectionServiceInterface $manager,
        protected LaboUserServiceInterface $userService,
        // protected AdminUrlGenerator $adminUrlGenerator,
        // protected WebsectionRepository $websectionRepository,
    ) {
        parent::__construct($manager, $userService);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
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
                yield TextField::new('name', 'Nom');
                yield TextField::new('sectiontype', 'Type de section');
                yield TextField::new('title', 'Titre de la section');
                yield AssociationField::new('mainmenu', 'Menu intégré');
                yield TextField::new('twigfileName', 'Nom du modèle');
                yield TextField::new('twigfile', 'Chemin du modèle')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('content', 'Texte de la section')->renderAsHtml();
                yield TextareaField::new('content', 'Texte compilé')->formatValue(function ($value) use ($info) {
                    return $info['entity']->dumpContent();
                })->setColumns(12);
                // PHOTO
                $photo = $info['entity']->getTwigfileMetadata()->getEasyadminField('photo', $pageName);
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
                yield CollectionField::new('categorys');
                yield TextareaField::new('relationOrderDetails', '[Rel.order info]')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('prefered', 'Section par défaut');
                yield BooleanField::new('enabled', 'Activée');
                yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name', 'Nom de la section')
                    ->setHelp('Utilisez un nom simple et pas trop long. <strong>Ce nom est uniquement utilisé pour l\'administration et n\'est pas affiché dans le contenu la page web</strong>.')
                    ->setColumns(6);
                yield ChoiceField::new('twigfile', 'Modèle de mise en page')
                    ->setChoices(static fn (Websection $websection): array => $websection->getTwigfileChoices() ?: [])
                    ->escapeHtml(false)
                    ->setFormTypeOption('by_reference', false)
                    ->setColumns(8);
                // yield ChoiceField::new('sectiontype', 'Type de section')
                //     ->setChoices(static fn (Websection $websection): array => $websection->getTwigfileMetadata()->getSectiontypeChoices() ?: [])
                //     ->setColumns(4);
                yield BooleanField::new('prefered', 'Section par défaut')->setColumns(4)->setHelp('Définir comme section attribuée par défaut dans une nouvelle page web');
                // cumputed form fields
                // TITLE
                $title = $info['entity']->getTwigfileMetadata()->getEasyadminField('title', $pageName);
                switch (true) {
                    case $title instanceof FieldInterface:
                        /** @var FieldTrait $title */
                        yield $title->setColumns(6);
                        break;
                    case $title === true:
                        yield TextField::new('title', 'Titre de la section')
                            ->setColumns(8)
                            ->setRequired(false);
                        break;
                }
                yield AssociationField::new('categorys')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Websection::class))
                    ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(4);
                // CONTENT
                $content = $info['entity']->getTwigfileMetadata()->getEasyadminField('content', $pageName);
                switch (true) {
                    case $content instanceof FieldInterface:
                        /** @var FieldTrait $content */
                        yield $content->setColumns(8);
                        break;
                    case $content === true:
                        yield TextEditorField::new('content', 'Texte de la section')
                            ->setNumOfRows(20)
                            ->formatValue(fn ($value) => Strings::markup($value))
                            ->setColumns(8);
                        break;
                }
                // MAINMENU
                $mainmenu = $info['entity']->getTwigfileMetadata()->getEasyadminField('mainmenu', $pageName);
                switch (true) {
                    case $mainmenu instanceof FieldInterface:
                        /** @var FieldTrait $mainmenu */
                        yield $mainmenu->setColumns(4);
                        break;
                    case $mainmenu === true:
                        yield AssociationField::new('mainmenu', 'Menu intégré')
                            ->setColumns(4);
                        break;
                }
                // PHOTO
                $photo = $info['entity']->getTwigfileMetadata()->getEasyadminField('photo', $pageName);
                switch (true) {
                    case $photo instanceof FieldInterface:
                        /** @var FieldTrait $photo */
                        yield $photo->setColumns(6);
                        break;
                    case $photo === true:
                        yield TextField::new('photo', 'Photo')
                            ->setFormType(PhotoType::class)
                            ->setColumns(6);
                        break;
                }
                yield BooleanField::new('enabled', 'Activée');
                yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN');
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name', 'Nom de la section')
                    ->setHelp('Utilisez un nom simple et pas trop long. <strong>Ce nom est uniquement utilisé pour l\'administration et n\'est pas affiché dans le contenu la page web</strong>.')
                    ->setColumns(6);
                $twigfile = $info['entity']->getTwigfileMetadata()->getEasyadminField('twigfile', $pageName);
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
                    // yield ChoiceField::new('sectiontype', 'Type de section')
                //     ->setChoices(static fn (Websection $websection): array => $websection->getTwigfileMetadata()->getSectiontypeChoices() ?: [])->setDisabled(true)
                //     ->setColumns(4);
                // cumputed form fields
                // TITLE
                $title = $info['entity']->getTwigfileMetadata()->getEasyadminField('title', $pageName);
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
                yield BooleanField::new('prefered', 'Section par défaut')->setColumns(2)->setHelp('Définir comme section attribuée par défaut dans une nouvelle page web');
                yield AssociationField::new('categorys')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Websection::class))
                    ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(4);
                // PHOTO
                $photo = $info['entity']->getTwigfileMetadata()->getEasyadminField('photo', $pageName);
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
                $content = $info['entity']->getTwigfileMetadata()->getEasyadminField('content', $pageName);
                switch (true) {
                    case $content instanceof FieldInterface:
                        /** @var FieldTrait $content */
                        yield $content->setColumns(8);
                        break;
                    case $content === true:
                        yield TextEditorField::new('content', 'Texte de la section')
                            ->setNumOfRows(20)
                            ->formatValue(fn ($value) => Strings::markup($value))
                            ->setColumns(8);
                        break;
                }
                // MAINMENU
                $mainmenu = $info['entity']->getTwigfileMetadata()->getEasyadminField('mainmenu', $pageName);
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
                yield BooleanField::new('enabled', 'Activée');
                yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN');
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield ThumbnailField::new('photo', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.item_photo'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield TextField::new('sectiontype', 'Type de section')->setTextAlign('center');
                // yield TextField::new('title', 'Titre');
                yield TextField::new('twigfileName', 'Modèle')->setTextAlign('center');
                yield TextEditorField::new('content', 'Texte de la section')
                    ->formatValue(fn ($value) => Strings::markup($value))
                    ->setTextAlign('center')
                    ->setSortable(false);
                // yield AssociationField::new('mainmenu', 'Menu intégré')->setTextAlign('center');
                yield AssociationField::new('owner', 'Propriétaire')->setTextAlign('center');
                yield BooleanField::new('prefered', 'Section par défaut')->setTextAlign('center');
                // yield BooleanField::new('enabled', 'Activée')->setTextAlign('center');
                // yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                break;
        }
    }

}